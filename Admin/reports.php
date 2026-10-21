<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan date range ke hisaab se summary aur report banti hai.

$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

function isReportDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function getRangeRows(mysqli $conn, string $sql, string $from, string $to): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ss', $from, $to);
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

$fromInput = trim($_GET['from'] ?? '');
$toInput = trim($_GET['to'] ?? '');
$fromDate = isReportDate($fromInput) ? $fromInput : $monthStart;
$toDate = isReportDate($toInput) ? $toInput : $today;
$rangeNotice = '';

if ($fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
    $rangeNotice = 'The dates were reordered so the start comes before the end.';
}

$statsRows = getRangeRows(
    $conn,
    "SELECT
        COUNT(*) AS total_bookings,
        COALESCE(SUM(booking_status = 'completed'), 0) AS completed_bookings,
        COALESCE(SUM(booking_status IN ('pending', 'provider_notified')), 0) AS pending_bookings,
        COALESCE(SUM(booking_status IN ('accepted', 'rescheduled', 'on_the_way', 'work_started')), 0) AS active_bookings,
        COALESCE(SUM(booking_status = 'cancelled'), 0) AS cancelled_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN price ELSE 0 END), 0) AS completed_value
     FROM bookings
     WHERE booking_date BETWEEN ? AND ?",
    $fromDate,
    $toDate
);
$stats = $statsRows[0] ?? [
    'total_bookings' => 0,
    'completed_bookings' => 0,
    'pending_bookings' => 0,
    'active_bookings' => 0,
    'cancelled_bookings' => 0,
    'completed_value' => 0,
];

$collectionRows = getRangeRows(
    $conn,
    "SELECT COALESCE(SUM(p.amount), 0) AS paid_amount
     FROM payments p
     INNER JOIN bookings b ON b.id = p.booking_id
     WHERE b.booking_date BETWEEN ? AND ?
       AND LOWER(p.payment_status) = 'paid'",
    $fromDate,
    $toDate
);
$paidAmount = (float)($collectionRows[0]['paid_amount'] ?? 0);

$statusRows = getRangeRows(
    $conn,
    "SELECT COALESCE(NULLIF(booking_status, ''), 'unknown') AS status,
            COUNT(*) AS total,
            COALESCE(SUM(price), 0) AS booking_value
     FROM bookings
     WHERE booking_date BETWEEN ? AND ?
     GROUP BY COALESCE(NULLIF(booking_status, ''), 'unknown')
     ORDER BY total DESC, status ASC",
    $fromDate,
    $toDate
);

$serviceRows = getRangeRows(
    $conn,
    "SELECT COALESCE(s.service_name, 'Service unavailable') AS service_name,
            COUNT(b.id) AS total_bookings,
            COALESCE(SUM(b.booking_status = 'completed'), 0) AS completed_bookings,
            COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN b.price ELSE 0 END), 0) AS completed_value
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.booking_date BETWEEN ? AND ?
     GROUP BY s.id, s.service_name
     ORDER BY total_bookings DESC, service_name ASC
     LIMIT 8",
    $fromDate,
    $toDate
);

$dailyRows = getRangeRows(
    $conn,
    "SELECT booking_date AS report_date,
            COUNT(*) AS total_bookings,
            COALESCE(SUM(booking_status = 'completed'), 0) AS completed_bookings,
            COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN price ELSE 0 END), 0) AS completed_value
     FROM bookings
     WHERE booking_date BETWEEN ? AND ?
     GROUP BY booking_date
     ORDER BY booking_date ASC",
    $fromDate,
    $toDate
);

$recentRows = getRangeRows(
    $conn,
    "SELECT b.booking_id,
            b.customer_name,
            b.booking_date,
            b.booking_status,
            b.price,
            COALESCE(s.service_name, 'Service unavailable') AS service_name,
            COALESCE(sp.provider_name, 'Not assigned') AS provider_name
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     LEFT JOIN service_providers sp ON sp.id = b.provider_id
     WHERE b.booking_date BETWEEN ? AND ?
     ORDER BY b.booking_date DESC, b.id DESC
     LIMIT 12",
    $fromDate,
    $toDate
);

if (($_GET['export'] ?? '') === 'csv') {
    $exportRows = getRangeRows(
        $conn,
        "SELECT b.booking_id,
                b.customer_name,
                b.mobile,
                COALESCE(s.service_name, 'Service unavailable') AS service_name,
                COALESCE(sp.provider_name, 'Not assigned') AS provider_name,
                b.booking_date,
                b.booking_time,
                b.booking_status,
                b.payment_status,
                b.price
         FROM bookings b
         LEFT JOIN services s ON s.id = b.service_id
         LEFT JOIN service_providers sp ON sp.id = b.provider_id
         WHERE b.booking_date BETWEEN ? AND ?
         ORDER BY b.booking_date DESC, b.id DESC",
        $fromDate,
        $toDate
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fixlify-booking-report-' . $fromDate . '-to-' . $toDate . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking ID', 'Customer', 'Mobile', 'Service', 'Provider', 'Booking Date', 'Time', 'Booking Status', 'Payment Status', 'Amount']);

    foreach ($exportRows as $row) {
        $values = array_values($row);
        foreach ($values as &$value) {
            $value = (string)$value;
            if (preg_match('/^[=+\-@\t\r]/', $value)) {
                $value = "'" . $value;
            }
        }
        unset($value);
        fputcsv($output, $values);
    }

    fclose($output);
    exit;
}

$maxDaily = 1;
foreach (array_slice($dailyRows, -14) as $day) {
    $maxDaily = max($maxDaily, (int)$day['total_bookings']);
}
$dailyRows = array_slice($dailyRows, -14);
$maxStatus = 1;
foreach ($statusRows as $statusRow) {
    $maxStatus = max($maxStatus, (int)$statusRow['total']);
}

function reportStatusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function reportStatusClass(string $status): string
{
    $allowed = ['pending', 'provider_notified', 'accepted', 'rescheduled', 'on_the_way', 'work_started', 'completed', 'cancelled', 'rejected'];
    return in_array($status, $allowed, true) ? 'status-' . $status : 'status-other';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Gofix Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--navy:#111827;--navy-soft:#1f2937;--blue:#2563eb;--blue-soft:#eff6ff;--ink:#172033;--muted:#667085;--line:#e6eaf0;--surface:#fff;--page:#f5f7fb;--green:#14804a;--amber:#b45309;--red:#b42318}
        *{box-sizing:border-box}
        body{margin:0;background:var(--page);color:var(--ink);font-family:Inter,Arial,sans-serif}
        a{color:inherit;text-decoration:none}
        button,input{font:inherit}
        .sidebar{position:fixed;inset:0 auto 0 0;width:250px;padding:26px 16px;background:var(--navy);color:#cbd5e1;overflow-y:auto;z-index:20}
        .brand{padding:0 14px 28px;color:#fff;font-size:24px;font-weight:800;letter-spacing:-.04em}
        .brand span{color:#60a5fa}
        .menu-label{padding:16px 14px 9px;color:#8290a5;font-size:10px;font-weight:800;letter-spacing:.13em;text-transform:uppercase}
        .nav-link{display:flex;align-items:center;gap:12px;margin:3px 0;padding:11px 14px;border-radius:10px;color:#cbd5e1;font-size:13px;font-weight:600;transition:.18s}
        .nav-link:hover,.nav-link.active{background:#263447;color:#fff}
        .nav-link.active{box-shadow:inset 3px 0 #60a5fa}
        .nav-icon{width:20px;text-align:center;font-size:16px}
        .nav-link.logout{margin-top:10px;color:#fecaca}
        .main{min-height:100vh;margin-left:250px;padding:30px clamp(18px,3vw,42px) 48px}
        .topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:25px}
        .heading h1{margin:0 0 6px;font-size:clamp(25px,3vw,32px);letter-spacing:-.045em}
        .heading p{margin:0;color:var(--muted);font-size:13px}
        .user{display:flex;align-items:center;gap:10px;padding:9px 13px;border:1px solid var(--line);border-radius:12px;background:#fff;color:#344054;font-size:13px;font-weight:650;white-space:nowrap}
        .user-avatar{display:grid;width:32px;height:32px;place-items:center;border-radius:10px;background:var(--blue-soft);color:var(--blue)}
        .mobile-menu{display:none;width:42px;height:42px;border:1px solid var(--line);border-radius:11px;background:#fff;color:var(--ink);font-size:18px}
        .filter-card,.panel,.metric{border:1px solid var(--line);border-radius:16px;background:var(--surface);box-shadow:0 5px 18px rgba(16,24,40,.035)}
        .filter-card{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:20px;padding:17px 19px}
        .filter-fields{display:flex;flex-wrap:wrap;align-items:end;gap:12px}
        .field{display:grid;gap:6px}
        .field label{color:#667085;font-size:11px;font-weight:750}
        .field input{min-height:39px;padding:0 10px;border:1px solid #d8dee8;border-radius:9px;background:#fff;color:#344054}
        .button{display:inline-flex;min-height:39px;align-items:center;justify-content:center;gap:8px;padding:0 14px;border:1px solid transparent;border-radius:9px;background:var(--blue);color:#fff;font-size:12px;font-weight:750;cursor:pointer}
        .button:hover{background:#1d4ed8}
        .button.secondary{border-color:#d8e1ee;background:#fff;color:#344054}
        .button.secondary:hover{background:#f8fafc}
        .filter-actions{display:flex;gap:9px;flex-wrap:wrap}
        .notice{margin:-5px 0 17px;padding:10px 13px;border:1px solid #fde68a;border-radius:10px;background:#fffbeb;color:#92400e;font-size:12px}
        .metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:18px}
        .metric{position:relative;overflow:hidden;padding:18px}
        .metric::after{position:absolute;right:-14px;top:-22px;width:78px;height:78px;border-radius:50%;background:var(--metric-tint,#eff6ff);content:"";opacity:.8}
        .metric-head{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:8px;color:#667085;font-size:12px;font-weight:650}
        .metric-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:10px;background:var(--metric-tint,#eff6ff);color:var(--metric-color,var(--blue));font-size:15px}
        .metric strong{position:relative;z-index:1;display:block;margin-top:13px;color:#101828;font-size:26px;font-weight:800;letter-spacing:-.04em}
        .metric small{display:block;margin-top:4px;color:#98a2b3;font-size:11px}
        .metric.blue{--metric-tint:#eff6ff;--metric-color:#2563eb}.metric.green{--metric-tint:#ecfdf3;--metric-color:#14804a}.metric.violet{--metric-tint:#f4f3ff;--metric-color:#6941c6}.metric.amber{--metric-tint:#fffaeb;--metric-color:#b54708}
        .charts{display:grid;grid-template-columns:minmax(270px,.8fr) minmax(0,1.5fr);gap:16px;margin-bottom:16px}
        .panel{padding:19px;min-width:0}
        .panel-heading{display:flex;justify-content:space-between;align-items:start;gap:12px;margin-bottom:17px}
        .panel-heading h2{margin:0;color:#182230;font-size:15px;font-weight:750}
        .panel-heading p{margin:5px 0 0;color:#98a2b3;font-size:11px}
        .muted-pill{padding:6px 9px;border-radius:999px;background:#f2f4f7;color:#667085;font-size:10px;font-weight:700;white-space:nowrap}
        .status-list{display:grid;gap:13px}
        .status-line{display:grid;grid-template-columns:102px minmax(50px,1fr) 32px;align-items:center;gap:9px}
        .status-name{overflow:hidden;color:#475467;font-size:11px;text-overflow:ellipsis;white-space:nowrap}
        .bar-track{height:8px;overflow:hidden;border-radius:999px;background:#f0f2f5}
        .bar-fill{height:100%;min-width:0;border-radius:999px;background:#4f7ff0}
        .status-completed{background:#12b76a}.status-cancelled,.status-rejected{background:#f04438}.status-pending,.status-provider_notified{background:#f79009}.status-accepted,.status-rescheduled,.status-on_the_way,.status-work_started{background:#7a5af8}
        .status-count{text-align:right;color:#344054;font-size:11px;font-weight:750}
        .daily-chart{display:flex;min-height:185px;align-items:end;gap:9px;padding:10px 3px 0;border-bottom:1px solid var(--line)}
        .day-column{display:flex;flex:1;min-width:0;height:165px;flex-direction:column;align-items:center;justify-content:end;gap:7px}
        .day-bar-wrap{display:flex;width:100%;height:130px;align-items:end;justify-content:center}
        .day-bar{width:min(30px,70%);min-height:3px;border-radius:6px 6px 2px 2px;background:linear-gradient(180deg,#60a5fa,#2563eb);transition:height .2s}
        .day-value{color:#475467;font-size:10px;font-weight:700}
        .day-label{overflow:hidden;width:100%;padding-bottom:8px;color:#98a2b3;font-size:9px;text-align:center;text-overflow:ellipsis;white-space:nowrap}
        .lower-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:16px}
        .table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;white-space:nowrap}
        th{padding:10px 11px;border-bottom:1px solid var(--line);color:#98a2b3;font-size:10px;font-weight:750;letter-spacing:.05em;text-align:left;text-transform:uppercase}
        td{padding:12px 11px;border-bottom:1px solid #f0f2f5;color:#475467;font-size:11px}
        tbody tr:last-child td{border-bottom:0}
        .primary-text{color:#182230;font-weight:700}
        .sub-text{display:block;margin-top:3px;color:#98a2b3;font-size:10px}
        .status-badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#f2f4f7;color:#475467;font-size:10px;font-weight:750}
        .status-badge.status-completed{background:#ecfdf3;color:#027a48}.status-badge.status-cancelled,.status-badge.status-rejected{background:#fef3f2;color:#b42318}.status-badge.status-pending,.status-badge.status-provider_notified{background:#fffaeb;color:#b54708}.status-badge.status-accepted,.status-badge.status-rescheduled,.status-badge.status-on_the_way,.status-badge.status-work_started{background:#f4f3ff;color:#6941c6}
        .empty{padding:26px 12px;color:#98a2b3;font-size:12px;text-align:center}
        .overlay{display:none}
        @media(max-width:1100px){.metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.charts{grid-template-columns:1fr}.status-list{grid-template-columns:repeat(2,minmax(0,1fr));column-gap:18px}}
        @media(max-width:720px){.sidebar{transform:translateX(-100%);transition:transform .2s}.sidebar.open{transform:translateX(0)}.main{margin-left:0;padding:19px 14px 35px}.mobile-menu{display:block}.topbar{align-items:flex-start}.heading{flex:1}.user{padding:6px 8px;font-size:11px}.user-avatar{width:28px;height:28px}.filter-card{align-items:stretch;flex-direction:column;padding:14px}.filter-fields{display:grid;grid-template-columns:1fr 1fr}.filter-actions{display:grid;grid-template-columns:1fr 1fr}.metrics{gap:10px}.metric{padding:14px}.metric strong{font-size:22px}.panel{padding:15px}.status-list{grid-template-columns:1fr}.overlay.show{display:block;position:fixed;inset:0;z-index:15;background:rgba(15,23,42,.45)}}
        @media(max-width:420px){.user-name{display:none}.heading p{font-size:11px}.daily-chart{gap:4px}.day-column{height:145px}.day-bar-wrap{height:112px}}
    </style>
    <link rel="stylesheet" href="admin-ui.css">
</head>
<body>
<?php require __DIR__ . '/sidebar.php'; ?>


<main class="main">
    <header class="topbar">
        <button class="mobile-menu" type="button" id="menuButton" aria-label="Open navigation">☰</button>
        <div class="heading">
            <h1>Reports &amp; Analytics</h1>
            <p>Track bookings, completed work, collections, and service performance.</p>
        </div>
        <div class="user"><span class="user-avatar">♙</span><span class="user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>

    <section class="filter-card" aria-label="Report filters">
        <form class="filter-fields" method="get" action="reports.php">
            <div class="field"><label for="from">From</label><input type="date" id="from" name="from" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label for="to">To</label><input type="date" id="to" name="to" value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>" required></div>
            <button class="button" type="submit">Apply dates</button>
        </form>
        <div class="filter-actions">
            <a class="button secondary" href="reports.php?from=<?= rawurlencode($fromDate) ?>&amp;to=<?= rawurlencode($toDate) ?>&amp;export=csv">↓ Export CSV</a>
            <a class="button secondary" href="reports.php">This month</a>
        </div>
    </section>

    <?php if ($rangeNotice !== ''): ?><div class="notice"><?= htmlspecialchars($rangeNotice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <section class="metrics" aria-label="Booking summary">
        <article class="metric blue"><div class="metric-head">Bookings<span class="metric-icon">▤</span></div><strong><?= number_format((int)$stats['total_bookings']) ?></strong><small>All statuses in the selected period</small></article>
        <article class="metric green"><div class="metric-head">Completed value<span class="metric-icon">✓</span></div><strong>₹<?= number_format((float)$stats['completed_value'], 2) ?></strong><small><?= number_format((int)$stats['completed_bookings']) ?> completed bookings</small></article>
        <article class="metric violet"><div class="metric-head">Payments collected<span class="metric-icon">₹</span></div><strong>₹<?= number_format($paidAmount, 2) ?></strong><small>Paid payment records for these bookings</small></article>
        <article class="metric amber"><div class="metric-head">Needs attention<span class="metric-icon">!</span></div><strong><?= number_format((int)$stats['pending_bookings'] + (int)$stats['active_bookings']) ?></strong><small><?= number_format((int)$stats['pending_bookings']) ?> pending · <?= number_format((int)$stats['active_bookings']) ?> active</small></article>
    </section>

    <section class="charts">
        <article class="panel">
            <div class="panel-heading"><div><h2>Booking status</h2><p>Distribution for the selected dates</p></div><span class="muted-pill"><?= number_format((int)$stats['cancelled_bookings']) ?> cancelled</span></div>
            <?php if ($statusRows): ?>
                <div class="status-list">
                    <?php foreach ($statusRows as $row): $status = (string)$row['status']; $count = (int)$row['total']; $width = (int)round(($count / $maxStatus) * 100); ?>
                        <div class="status-line">
                            <span class="status-name"><?= htmlspecialchars(reportStatusLabel($status), ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="bar-track"><div class="bar-fill <?= htmlspecialchars(reportStatusClass($status), ENT_QUOTES, 'UTF-8') ?>" style="width:<?= $width ?>%"></div></div>
                            <span class="status-count"><?= number_format($count) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty">No bookings in this date range.</div><?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel-heading"><div><h2>Daily bookings</h2><p>Showing up to the latest 14 days in this range</p></div><span class="muted-pill"><?= htmlspecialchars(date('d M', strtotime($fromDate)), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d M Y', strtotime($toDate)), ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php if ($dailyRows): ?>
                <div class="daily-chart" role="img" aria-label="Daily booking counts shown as bars">
                    <?php foreach ($dailyRows as $day): $count = (int)$day['total_bookings']; $height = max(3, (int)round(($count / $maxDaily) * 112)); ?>
                        <div class="day-column" title="<?= htmlspecialchars(date('d M Y', strtotime($day['report_date'])), ENT_QUOTES, 'UTF-8') ?>: <?= $count ?> bookings">
                            <span class="day-value"><?= $count ?></span>
                            <div class="day-bar-wrap"><div class="day-bar" style="height:<?= $height ?>px"></div></div>
                            <span class="day-label"><?= htmlspecialchars(date('d M', strtotime($day['report_date'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty">No daily booking data for this date range.</div><?php endif; ?>
        </article>
    </section>

    <section class="lower-grid">
        <article class="panel">
            <div class="panel-heading"><div><h2>Service performance</h2><p>Most booked services in the selected period</p></div><span class="muted-pill">Top 8</span></div>
            <?php if ($serviceRows): ?>
                <div class="table-wrap"><table>
                    <thead><tr><th>Service</th><th>Bookings</th><th>Completed</th><th>Completed value</th></tr></thead>
                    <tbody><?php foreach ($serviceRows as $row): ?>
                        <tr><td class="primary-text"><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((int)$row['total_bookings']) ?></td><td><?= number_format((int)$row['completed_bookings']) ?></td><td class="primary-text">₹<?= number_format((float)$row['completed_value'], 2) ?></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php else: ?><div class="empty">No service bookings found for this period.</div><?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel-heading"><div><h2>Recent bookings</h2><p>Latest bookings within the selected dates</p></div><a class="muted-pill" href="bookings.php">Open bookings</a></div>
            <?php if ($recentRows): ?>
                <div class="table-wrap"><table>
                    <thead><tr><th>Booking</th><th>Service / Customer</th><th>Provider</th><th>Date</th><th>Status</th><th>Amount</th></tr></thead>
                    <tbody><?php foreach ($recentRows as $row): $status = (string)$row['booking_status']; ?>
                        <tr>
                            <td class="primary-text"><?= htmlspecialchars($row['booking_id'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="primary-text"><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></span><span class="sub-text"><?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($row['provider_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['booking_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="status-badge <?= htmlspecialchars(reportStatusClass($status), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(reportStatusLabel($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="primary-text">₹<?= number_format((float)$row['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php else: ?><div class="empty">No bookings found for this period.</div><?php endif; ?>
        </article>
    </section>
</main>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const menuButton = document.getElementById('menuButton');
function closeMenu() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
}
menuButton.addEventListener('click', function () {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
});
sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeMenu);
});
</script>
<script src="history-lock.js?v=<?= (string) filemtime(__DIR__ . '/history-lock.js') ?>"></script>
<script src="admin-ui.js"></script>
</body>
</html>
