<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

// Require victim access
require_victim();

// Get victim ID for this user
$victim_id = get_victim_id();
if (!$victim_id) {
    die('Error: No victim record linked to your account. Please contact administrator.');
}

// Fetch dashboard metrics (VICTIM - Personal data only)
$metrics = [
    'my_open_cases' => 0,
    'my_closed_cases' => 0,
    'my_appointments' => 0,
    'recent_updates' => 0
];

// Count MY open cases
$sql = "SELECT COUNT(*) as cnt FROM case_t WHERE case_status = 'Open' AND victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$metrics['my_open_cases'] = $row['CNT'];

// Count MY closed cases
$sql = "SELECT COUNT(*) as cnt FROM case_t WHERE case_status = 'Closed' AND victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$metrics['my_closed_cases'] = $row['CNT'];

// Count MY recent updates (last 7 days)
$sql = "SELECT COUNT(*) as cnt FROM case_t WHERE open_date >= SYSDATE - 7 AND victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$metrics['recent_updates'] = $row['CNT'];

// Count MY upcoming appointments
$sql = "SELECT COUNT(*) as cnt FROM appointment a 
        JOIN case_t c ON a.case_id = c.case_id 
        WHERE a.appt_datetime >= SYSDATE AND c.victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$metrics['my_appointments'] = $row['CNT'];

// Fetch MY upcoming appointments
$upcoming_appointments = [];
$sql = "SELECT * FROM (
        SELECT a.appointment_id, a.appt_datetime, a.location, a.status, c.case_type 
        FROM appointment a 
        LEFT JOIN case_t c ON a.case_id = c.case_id 
        WHERE a.appt_datetime >= SYSDATE AND c.victim_id = :victim_id
        ORDER BY a.appt_datetime ASC
        ) WHERE ROWNUM <= 3";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $upcoming_appointments[] = $row;
}

// Fetch MY active cases
$active_cases = [];
$sql = "SELECT * FROM (
        SELECT case_id, case_type, case_status, open_date 
        FROM case_t 
        WHERE case_status IN ('Open', 'In-Process') AND victim_id = :victim_id
        ORDER BY open_date DESC
        ) WHERE ROWNUM <= 4";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $active_cases[] = $row;
}

// Fetch victim information
$sql = "SELECT victim_name FROM victim WHERE victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$victim_info = oci_fetch_assoc($stmt);
$victim_name = $victim_info['VICTIM_NAME'] ?? 'User';

$user_initials = get_user_initials($victim_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - AidNexus</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h2 { font-size: 1.75rem; color: var(--text-dark); font-weight: 700; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--text-dark); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .user-details { text-align: right; }
        .user-name { font-weight: 600; font-size: 0.9rem; color: var(--text-dark); }
        .user-email { font-size: 0.75rem; color: var(--text-light); }
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .metric-card { background: white; padding: 25px; border-radius: 8px; border-left: 4px solid var(--primary-green); box-shadow: var(--shadow); }
        .metric-value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .metric-label { font-size: 0.9rem; color: var(--text-light); font-weight: 500; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .panel { background: white; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); }
        .panel h3 { font-size: 1.25rem; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; }
        .item { padding: 15px 0; border-bottom: 1px solid var(--border); }
        .item:last-child { border-bottom: none; }
        .item-title { font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .item-meta { font-size: 0.85rem; color: var(--text-light); }
    </style>
</head>
<body>
    <?php include 'includes/victim_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>My Dashboard</h2>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($victim_name); ?></div>
                    <div class="user-email">@ victim</div>
                </div>
                <div class="user-avatar"><?php echo $user_initials; ?></div>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['my_open_cases']; ?></div>
                <div class="metric-label">My Open Cases</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['my_closed_cases']; ?></div>
                <div class="metric-label">My Closed Cases</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['my_appointments']; ?></div>
                <div class="metric-label">My Appointments</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['recent_updates']; ?></div>
                <div class="metric-label">Recent Updates</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="panel">
                <h3>My Upcoming Appointments</h3>
                <?php if (empty($upcoming_appointments)): ?>
                    <p style="color: var(--text-light); padding: 20px 0;">No upcoming appointments.</p>
                <?php else: ?>
                    <?php foreach ($upcoming_appointments as $appt): ?>
                        <div class="item">
                            <div class="item-title"><?php echo htmlspecialchars($appt['CASE_TYPE'] ?? 'Appointment'); ?></div>
                            <div class="item-meta"><?php echo format_datetime($appt['APPT_DATETIME']); ?> | <?php echo htmlspecialchars($appt['LOCATION']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>My Active Cases</h3>
                <?php if (empty($active_cases)): ?>
                    <p style="color: var(--text-light); padding: 20px 0;">No active cases.</p>
                <?php else: ?>
                    <?php foreach ($active_cases as $case): ?>
                        <div class="item">
                            <div class="item-title">CS-<?php echo $case['CASE_ID']; ?>: <?php echo htmlspecialchars($case['CASE_TYPE']); ?></div>
                            <div class="item-meta"><?php echo htmlspecialchars($case['CASE_STATUS']); ?> | <?php echo format_date($case['OPEN_DATE']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
