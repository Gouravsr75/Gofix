<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Hinglish: Yahan customer ke messages padh aur manage karte hain.

/* =====================================================
   MARK READ
===================================================== */

if (isset($_GET["read"])) {

    $id = (int)$_GET["read"];

    if ($id > 0) {

        $stmt = $conn->prepare(
            "UPDATE contact_messages
             SET status='Read'
             WHERE id=?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_contact.php");
    exit;
}


/* =====================================================
   MARK REPLIED
===================================================== */

if (isset($_GET["replied"])) {

    $id = (int)$_GET["replied"];

    if ($id > 0) {

        $stmt = $conn->prepare(
            "UPDATE contact_messages
             SET status='Replied'
             WHERE id=?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_contact.php");
    exit;
}


/* =====================================================
   DELETE
===================================================== */

if (isset($_GET["delete"])) {

    $id = (int)$_GET["delete"];

    if ($id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM contact_messages
             WHERE id=?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_contact.php");
    exit;
}


/* =====================================================
   SEARCH + FILTER
===================================================== */

$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";


$sql = "
    SELECT
        id,
        full_name,
        email,
        mobile,
        subject,
        message,
        status,
        created_at
    FROM contact_messages
    WHERE 1=1
";


$params = [];
$types = "";


/* SEARCH */

if ($search !== "") {

    $sql .= "
        AND (
            full_name LIKE ?
            OR email LIKE ?
            OR mobile LIKE ?
            OR subject LIKE ?
            OR message LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sssss";
}


/* STATUS */

if (
    $status === "New" ||
    $status === "Read" ||
    $status === "Replied"
) {

    $sql .= " AND status=?";

    $params[] = $status;

    $types .= "s";
}


$sql .= " ORDER BY id DESC";


$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param($types, ...$params);
}


$stmt->execute();

$result = $stmt->get_result();


/* =====================================================
   DASHBOARD COUNTS
===================================================== */

$totalResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages"
);

$totalMessages =
    $totalResult->fetch_assoc()["total"];


$newResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE status='New'"
);

$newMessages =
    $newResult->fetch_assoc()["total"];


$readResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE status='Read'"
);

$readMessages =
    $readResult->fetch_assoc()["total"];


$repliedResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM contact_messages
     WHERE status='Replied'"
);

$repliedMessages =
    $repliedResult->fetch_assoc()["total"];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Contact Messages | HomeEase Admin</title>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =====================================================
   GLOBAL
===================================================== */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:
    Arial,
    Helvetica,
    sans-serif;

    background:#f8fafc;

    color:#0f172a;
}

a{
    text-decoration:none;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar{

    position:fixed;

    left:0;
    top:0;

    width:240px;

    height:100vh;

    background:#0f172a;

    color:white;

    padding:25px 15px;

    z-index:1000;
}

.logo{

    display:flex;

    align-items:center;

    gap:10px;

    font-size:20px;

    font-weight:800;

    padding:0 12px 30px;
}

.logo i{

    color:#3b82f6;
}

.nav-title{

    font-size:10px;

    color:#94a3b8;

    text-transform:uppercase;

    margin:20px 12px 8px;
}

.nav-item{

    display:flex;

    align-items:center;

    gap:12px;

    color:#cbd5e1;

    padding:12px;

    border-radius:9px;

    margin-bottom:5px;

    font-size:13px;

    transition:.2s;
}

.nav-item:hover,
.nav-item.active{

    background:#1e40af;

    color:white;
}

.nav-item i{

    width:18px;

    text-align:center;
}


/* =====================================================
   MAIN
===================================================== */

.main{

    margin-left:240px;

    min-height:100vh;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar{

    height:70px;

    background:white;

    border-bottom:1px solid #e2e8f0;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;
}

.topbar-title h1{

    font-size:20px;

    margin-bottom:3px;
}

.topbar-title p{

    color:#64748b;

    font-size:12px;
}

.admin-user{

    display:flex;

    align-items:center;

    gap:10px;

    font-size:13px;

    font-weight:600;
}

.admin-avatar{

    width:36px;

    height:36px;

    border-radius:50%;

    background:#dbeafe;

    color:#2563eb;

    display:flex;

    align-items:center;

    justify-content:center;
}


/* =====================================================
   CONTENT
===================================================== */

.content{

    padding:30px;
}


/* =====================================================
   STAT CARDS
===================================================== */

.stats{

    display:grid;

    grid-template-columns:
    repeat(4,1fr);

    gap:18px;

    margin-bottom:25px;
}

.stat-card{

    background:white;

    border:1px solid #e2e8f0;

    border-radius:14px;

    padding:20px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    box-shadow:
    0 4px 15px
    rgba(15,23,42,.04);
}

.stat-left p{

    font-size:12px;

    color:#64748b;

    margin-bottom:7px;
}

.stat-left h2{

    font-size:25px;
}

.stat-icon{

    width:46px;

    height:46px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:18px;
}

.blue{

    background:#dbeafe;

    color:#2563eb;
}

.orange{

    background:#ffedd5;

    color:#ea580c;
}

.gray{

    background:#f1f5f9;

    color:#475569;
}

.green{

    background:#dcfce7;

    color:#16a34a;
}


/* =====================================================
   TOOLBAR
===================================================== */

.toolbar{

    background:white;

    border:1px solid #e2e8f0;

    padding:18px;

    border-radius:14px 14px 0 0;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;
}

.filters{

    display:flex;

    align-items:center;

    gap:10px;

    flex:1;
}

.search-box{

    position:relative;

    max-width:400px;

    width:100%;
}

.search-box i{

    position:absolute;

    left:13px;

    top:50%;

    transform:translateY(-50%);

    color:#94a3b8;

    font-size:13px;
}

.search-box input{

    width:100%;

    height:40px;

    border:1px solid #e2e8f0;

    border-radius:9px;

    padding:0 12px 0 38px;

    outline:none;

    font-size:12px;
}

.search-box input:focus{

    border-color:#2563eb;

    box-shadow:
    0 0 0 3px
    rgba(37,99,235,.08);
}

.status-select{

    height:40px;

    border:1px solid #e2e8f0;

    border-radius:9px;

    padding:0 12px;

    background:white;

    outline:none;

    font-size:12px;

    color:#334155;
}

.filter-btn{

    height:40px;

    border:none;

    background:#2563eb;

    color:white;

    border-radius:9px;

    padding:0 16px;

    cursor:pointer;

    font-size:12px;

    font-weight:600;
}

.reset-btn{

    height:40px;

    display:flex;

    align-items:center;

    padding:0 14px;

    background:#f1f5f9;

    color:#334155;

    border-radius:9px;

    font-size:12px;
}


/* =====================================================
   TABLE
===================================================== */

.table-card{

    background:white;

    border:1px solid #e2e8f0;

    border-top:none;

    border-radius:0 0 14px 14px;

    overflow:hidden;
}

.table-wrapper{

    width:100%;

    overflow-x:auto;
}

table{

    width:100%;

    min-width:1100px;

    border-collapse:collapse;
}

th{

    background:#f8fafc;

    color:#64748b;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.4px;

    font-weight:700;

    padding:14px 15px;

    text-align:left;

    border-bottom:1px solid #e2e8f0;
}

td{

    padding:15px;

    border-bottom:1px solid #f1f5f9;

    vertical-align:top;

    font-size:12px;
}

tbody tr{

    transition:.2s;
}

tbody tr:hover{

    background:#f8fbff;
}

.id{

    color:#94a3b8;

    font-weight:600;
}

.name{

    font-weight:700;

    color:#0f172a;
}

.contact{

    line-height:1.7;

    color:#64748b;
}

.subject{

    font-weight:600;

    max-width:180px;
}

.message{

    max-width:300px;

    color:#475569;

    line-height:1.6;

    white-space:pre-wrap;

    word-break:break-word;
}

.date{

    white-space:nowrap;

    color:#64748b;
}


/* =====================================================
   BADGE
===================================================== */

.badge{

    display:inline-flex;

    align-items:center;

    gap:5px;

    padding:5px 9px;

    border-radius:20px;

    font-size:10px;

    font-weight:700;
}

.badge-new{

    background:#dbeafe;

    color:#1d4ed8;
}

.badge-read{

    background:#f1f5f9;

    color:#475569;
}

.badge-replied{

    background:#dcfce7;

    color:#15803d;
}


/* =====================================================
   ACTIONS
===================================================== */

.actions{

    display:flex;

    gap:5px;

    flex-wrap:wrap;
}

.action{

    width:30px;

    height:30px;

    border-radius:7px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:11px;

    transition:.2s;
}

.view{

    background:#eff6ff;

    color:#2563eb;
}

.view:hover{

    background:#2563eb;

    color:white;
}

.read{

    background:#f1f5f9;

    color:#475569;
}

.read:hover{

    background:#475569;

    color:white;
}

.reply{

    background:#ecfdf5;

    color:#059669;
}

.reply:hover{

    background:#059669;

    color:white;
}

.delete{

    background:#fef2f2;

    color:#dc2626;
}

.delete:hover{

    background:#dc2626;

    color:white;
}


/* =====================================================
   EMPTY
===================================================== */

.empty{

    padding:70px 20px;

    text-align:center;

    color:#64748b;
}

.empty-icon{

    width:60px;

    height:60px;

    margin:0 auto 15px;

    border-radius:50%;

    background:#f1f5f9;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;
}

.empty h3{

    color:#334155;

    margin-bottom:5px;
}


/* =====================================================
   MODAL
===================================================== */

.modal{

    position:fixed;

    inset:0;

    background:
    rgba(15,23,42,.55);

    display:none;

    align-items:center;

    justify-content:center;

    padding:20px;

    z-index:2000;
}

.modal.show{

    display:flex;
}

.modal-box{

    width:100%;

    max-width:600px;

    background:white;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
    0 25px 70px
    rgba(0,0,0,.20);
}

.modal-header{

    padding:18px 20px;

    border-bottom:1px solid #e2e8f0;

    display:flex;

    justify-content:space-between;

    align-items:center;
}

.modal-header h3{

    font-size:17px;
}

.close{

    width:32px;

    height:32px;

    border:none;

    border-radius:8px;

    background:#f1f5f9;

    cursor:pointer;

    color:#475569;
}

.modal-body{

    padding:22px;
}

.detail-row{

    margin-bottom:16px;
}

.detail-label{

    color:#64748b;

    font-size:10px;

    text-transform:uppercase;

    font-weight:700;

    margin-bottom:5px;
}

.detail-value{

    color:#0f172a;

    font-size:13px;

    line-height:1.6;
}

.detail-message{

    background:#f8fafc;

    padding:15px;

    border-radius:10px;

    white-space:pre-wrap;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:1000px){

    .sidebar{

        width:70px;

        padding:20px 8px;
    }

    .logo span,
    .nav-title,
    .nav-item span{

        display:none;
    }

    .logo{

        justify-content:center;

        padding:0 0 30px;
    }

    .nav-item{

        justify-content:center;
    }

    .main{

        margin-left:70px;
    }

    .stats{

        grid-template-columns:
        repeat(2,1fr);
    }
}

@media(max-width:650px){

    .topbar{

        padding:0 15px;
    }

    .topbar-title h1{

        font-size:17px;
    }

    .topbar-title p{

        display:none;
    }

    .content{

        padding:18px 12px;
    }

    .admin-user{

        display:none;
    }

    .stats{

        grid-template-columns:1fr 1fr;

        gap:10px;
    }

    .stat-card{

        padding:14px;
    }

    .stat-left h2{

        font-size:21px;
    }

    .stat-icon{

        width:38px;

        height:38px;

        font-size:14px;
    }

    .toolbar{

        flex-direction:column;

        align-items:stretch;
    }

    .filters{

        flex-direction:column;

        align-items:stretch;
    }

    .search-box{

        max-width:none;
    }

    .status-select,
    .filter-btn,
    .reset-btn{

        width:100%;
    }
}

</style>

    <link rel="stylesheet" href="admin-ui.css">
</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php require __DIR__ . '/sidebar.php'; ?>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


<!-- TOPBAR -->

<header class="topbar">

    <div class="topbar-title">

        <h1>
            Contact Messages
        </h1>

        <p>
            Manage customer enquiries
        </p>

    </div>


    <div class="admin-user">

        <div class="admin-avatar">

            <i class="fa-solid fa-user"></i>

        </div>

        Admin

    </div>

</header>


<!-- CONTENT -->

<div class="content">


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


    <div class="stat-card">

        <div class="stat-left">

            <p>
                Total Messages
            </p>

            <h2>
                <?= (int)$totalMessages ?>
            </h2>

        </div>

        <div class="stat-icon blue">

            <i class="fa-solid fa-envelope"></i>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-left">

            <p>
                New Messages
            </p>

            <h2>
                <?= (int)$newMessages ?>
            </h2>

        </div>

        <div class="stat-icon orange">

            <i class="fa-solid fa-bell"></i>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-left">

            <p>
                Read
            </p>

            <h2>
                <?= (int)$readMessages ?>
            </h2>

        </div>

        <div class="stat-icon gray">

            <i class="fa-solid fa-envelope-open"></i>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-left">

            <p>
                Replied
            </p>

            <h2>
                <?= (int)$repliedMessages ?>
            </h2>

        </div>

        <div class="stat-icon green">

            <i class="fa-solid fa-reply"></i>

        </div>

    </div>


</div>


<!-- =====================================================
     FILTER
===================================================== -->

<form
    method="GET"
    class="toolbar"
>

    <div class="filters">


        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                name="search"
                placeholder="Search name, email, subject..."
                value="<?= htmlspecialchars($search) ?>"
            >

        </div>


        <select
            name="status"
            class="status-select"
        >

            <option value="">
                All Status
            </option>

            <option
                value="New"
                <?= $status === "New" ? "selected" : "" ?>
            >
                New
            </option>

            <option
                value="Read"
                <?= $status === "Read" ? "selected" : "" ?>
            >
                Read
            </option>

            <option
                value="Replied"
                <?= $status === "Replied" ? "selected" : "" ?>
            >
                Replied
            </option>

        </select>


        <button
            type="submit"
            class="filter-btn"
        >

            <i class="fa-solid fa-filter"></i>

            Filter

        </button>


        <a
            href="admin_contact.php"
            class="reset-btn"
        >

            Reset

        </a>


    </div>

</form>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-card">

<div class="table-wrapper">


<?php if ($result->num_rows > 0): ?>


<table>

<thead>

<tr>

<th>
ID
</th>

<th>
Customer
</th>

<th>
Contact
</th>

<th>
Subject
</th>

<th>
Message
</th>

<th>
Status
</th>

<th>
Date
</th>

<th>
Actions
</th>

</tr>

</thead>


<tbody>


<?php while ($row = $result->fetch_assoc()): ?>


<tr>


<td class="id">

#
<?= (int)$row["id"] ?>

</td>


<td>

<div class="name">

<?= htmlspecialchars(
    $row["full_name"]
) ?>

</div>

</td>


<td class="contact">

<?= htmlspecialchars(
    $row["email"]
) ?>

<br>

<?= htmlspecialchars(
    $row["mobile"] ?: "No mobile"
) ?>

</td>


<td class="subject">

<?= htmlspecialchars(
    $row["subject"]
) ?>

</td>


<td class="message">

<?= htmlspecialchars(
    $row["message"]
) ?>

</td>


<td>


<?php

$statusClass =
    strtolower($row["status"]);

$statusIcon =
    $row["status"] === "New"
    ? "fa-circle"
    :
    (
        $row["status"] === "Read"
        ? "fa-envelope-open"
        : "fa-reply"
    );

?>


<span
    class="badge badge-<?= $statusClass ?>"
>

<i
    class="fa-solid <?= $statusIcon ?>"
></i>

<?= htmlspecialchars(
    $row["status"]
) ?>

</span>


</td>


<td class="date">

<?= date(
    "d M Y",
    strtotime($row["created_at"])
) ?>

<br>

<small>

<?= date(
    "h:i A",
    strtotime($row["created_at"])
) ?>

</small>

</td>


<td>


<div class="actions">


<!-- VIEW -->

<button
    type="button"
    class="action view"
    title="View Message"

    onclick='viewMessage(
        <?= json_encode(
            $row["full_name"],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>,

        <?= json_encode(
            $row["email"],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>,

        <?= json_encode(
            $row["mobile"],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>,

        <?= json_encode(
            $row["subject"],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>,

        <?= json_encode(
            $row["message"],
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        ) ?>
    )'
>

<i class="fa-solid fa-eye"></i>

</button>


<!-- READ -->

<?php if ($row["status"] === "New"): ?>

<a
    href="admin_contact.php?read=<?= (int)$row["id"] ?>"
    class="action read"
    title="Mark Read"
>

<i class="fa-solid fa-envelope-open"></i>

</a>

<?php endif; ?>


<!-- REPLIED -->

<?php if ($row["status"] !== "Replied"): ?>

<a
    href="admin_contact.php?replied=<?= (int)$row["id"] ?>"
    class="action reply"
    title="Mark Replied"
>

<i class="fa-solid fa-reply"></i>

</a>

<?php endif; ?>


<!-- DELETE -->

<a
    href="admin_contact.php?delete=<?= (int)$row["id"] ?>"
    class="action delete"
    title="Delete"
    onclick="
        return confirm(
            'Are you sure you want to delete this message?'
        );
    "
>

<i class="fa-solid fa-trash"></i>

</a>


</div>


</td>


</tr>


<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty">

    <div class="empty-icon">

        <i class="fa-regular fa-envelope"></i>

    </div>

    <h3>
        No Messages Found
    </h3>

    <p>
        There are no contact messages matching your search.
    </p>

</div>


<?php endif; ?>


</div>

</div>


</div>

</main>


<!-- =====================================================
     MESSAGE MODAL
===================================================== -->

<div
    class="modal"
    id="messageModal"
>

    <div class="modal-box">


        <div class="modal-header">

            <h3>
                Message Details
            </h3>

            <button
                type="button"
                class="close"
                onclick="closeModal()"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <div class="modal-body">


            <div class="detail-row">

                <div class="detail-label">
                    Customer
                </div>

                <div
                    class="detail-value"
                    id="modalName"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Email
                </div>

                <div
                    class="detail-value"
                    id="modalEmail"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Mobile
                </div>

                <div
                    class="detail-value"
                    id="modalMobile"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Subject
                </div>

                <div
                    class="detail-value"
                    id="modalSubject"
                ></div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Message
                </div>

                <div
                    class="detail-value detail-message"
                    id="modalMessage"
                ></div>

            </div>


        </div>

    </div>

</div>


<script>

/* =====================================================
   VIEW MESSAGE
===================================================== */

function viewMessage(
    name,
    email,
    mobile,
    subject,
    message
){

    document.getElementById(
        "modalName"
    ).textContent = name;

    document.getElementById(
        "modalEmail"
    ).textContent = email;

    document.getElementById(
        "modalMobile"
    ).textContent =
        mobile || "Not provided";

    document.getElementById(
        "modalSubject"
    ).textContent = subject;

    document.getElementById(
        "modalMessage"
    ).textContent = message;

    document.getElementById(
        "messageModal"
    ).classList.add("show");
}


/* =====================================================
   CLOSE MODAL
===================================================== */

function closeModal(){

    document.getElementById(
        "messageModal"
    ).classList.remove("show");
}


/* =====================================================
   CLOSE ON OUTSIDE CLICK
===================================================== */

document.getElementById(
    "messageModal"
).addEventListener(
    "click",
    function(e){

        if(e.target === this){

            closeModal();

        }

    }
);


/* =====================================================
   ESCAPE
===================================================== */

document.addEventListener(
    "keydown",
    function(e){

        if(e.key === "Escape"){

            closeModal();

        }

    }
);

</script>


<script src="admin-ui.js"></script>
</body>

</html>

<?php

$stmt->close();

$conn->close();

?>
