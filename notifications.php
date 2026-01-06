<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_victim();

$victim_id = get_victim_id();
if (!$victim_id) {
    die('Error: No victim record linked to your account.');
}

// Fetch notifications for this victim
$notifications = [];
$sql = "SELECT * FROM notification WHERE victim_id = :victim_id ORDER BY sent_datetime DESC";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $notifications[] = $row;
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AidNexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #2C3E50;
            --sidebar-hover: #34495E;
            --primary-green: #4CAF50;
            --bg-page: #F5F5F5;
            --white: #FFFFFF;
            --text-dark: #2C3E50;
            --text-light: #7F8C8D;
            --border: #E0E0E0;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; }
        .sidebar-header { padding: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header svg { width: 24px; height: 24px; }
        .sidebar-header h1 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-nav { flex-grow: 1; padding: 20px 0; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.8); cursor: pointer; transition: all 0.2s; font-size: 0.9rem; }
        .nav-item:hover { background-color: var(--sidebar-hover); color: white; }
        .nav-item.active { background-color: var(--primary-green); color: white; border-left: 4px solid white; }
        .nav-item svg { width: 18px; height: 18px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; flex-grow: 1; padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-size: 1.75rem; color: var(--text-dark); font-weight: 700; }
        .notification-list { background: white; border-radius: 8px; box-shadow: var(--shadow); }
        .notification-item { padding: 20px; border-bottom: 1px solid var(--border); display: flex; gap: 15px; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item.unread { background-color: #f0f9ff; }
        .notification-icon { width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-green); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notification-content { flex-grow: 1; }
        .notification-title { font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .notification-message { color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px; }
        .notification-date { font-size: 0.8rem; color: var(--text-light); }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
    </style>
</head>
<body>
    <?php include 'includes/victim_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Notifications</h2>
        </div>

        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 64px; height: 64px; margin: 0 auto 20px; color: #cbd5e0;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <h3 style="margin-bottom: 10px;">No notifications yet</h3>
                    <p>You'll see updates about your cases and appointments here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['IS_READ'] ? '' : 'unread'; ?>">
                        <div class="notification-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notif['NOTIFICATION_TYPE']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notif['MESSAGE']); ?></div>
                            <div class="notification-date"><?php echo format_datetime($notif['SENT_DATETIME']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
