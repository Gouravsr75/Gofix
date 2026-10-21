<?php
// Hinglish: Ek hi sidebar sab admin pages par use hota hai; current page active dikhta hai.
$adminMenu = [
    ['dashboard.php', 'Dashboard', 'Overview'],
    ['bookings.php', 'Bookings', 'Orders'],
    ['providers.php', 'Providers', 'People'],
    ['customers.php', 'Customers', 'People'],
    ['services.php', 'Services', 'Manage'],
    ['payments.php', 'Payments', 'Manage'],
    ['reports.php', 'Reports', 'Manage'],
    ['admin_contact.php', 'Messages', 'Manage'],
];
$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? '');
$adminDisplayName = $_SESSION['admin_name'] ?? ($admin_name ?? 'Administrator');
$lastAdminGroup = '';
?>

<aside class="sidebar" id="sidebar">
    <a class="admin-brand" href="dashboard.php">
        <span class="admin-brand-mark">G</span>
        <span>Gofix <small>Admin</small></span>
    </a>

    <div class="admin-profile">
        <span class="admin-profile-label">SIGNED IN</span>
        <strong><?= htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>

    <nav class="admin-menu" aria-label="Admin navigation">
        <?php foreach ($adminMenu as [$href, $label, $group]): ?>
            <?php if ($group !== $lastAdminGroup): ?>
                <span class="admin-menu-group"><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></span>
                <?php $lastAdminGroup = $group; ?>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $currentAdminPage === $href ? 'active' : '' ?>"
               <?= $currentAdminPage === $href ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
        <span class="admin-menu-group">Account</span>
        <a class="admin-logout" href="logout.php">Logout</a>
    </nav>
</aside>

<div class="overlay" id="overlay" onclick="closeMenu()" aria-hidden="true"></div>
