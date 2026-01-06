<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_admin();

// Fetch statistics for reports
$stats = [];

// Total victims
$sql = "SELECT COUNT(*) as cnt FROM victim WHERE is_active = 1";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$stats['total_victims'] = $row['CNT'];

// Cases by status
$sql = "SELECT case_status, COUNT(*) as cnt FROM case_t GROUP BY case_status";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $stats['cases_' . strtolower(str_replace('-', '_', $row['CASE_STATUS']))] = $row['CNT'];
}

// Total appointments
$sql = "SELECT COUNT(*) as cnt FROM appointment";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$stats['total_appointments'] = $row['CNT'];

// Total doctors
$sql = "SELECT COUNT(*) as cnt FROM doctor";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$stats['total_doctors'] = $row['CNT'];

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - AidNexus</title>
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
        .sidebar-header .admin-badge { background-color: var(--primary-green); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .sidebar-nav { flex-grow: 1; padding: 20px 0; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.8); cursor: pointer; transition: all 0.2s; font-size: 0.9rem; }
        .nav-item:hover { background-color: var(--sidebar-hover); color: white; }
        .nav-item.active { background-color: var(--primary-green); color: white; border-left: 4px solid white; }
        .nav-item svg { width: 18px; height: 18px; }
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; flex-grow: 1; padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-size: 1.75rem; color: var(--text-dark); font-weight: 700; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .stat-label { font-size: 0.9rem; color: var(--text-light); font-weight: 500; }
        .report-section { background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow); margin-bottom: 20px; }
        .report-section h3 { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 20px; }
        .chart-placeholder { height: 300px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--text-light); }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Reports & Analytics</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_victims']; ?></div>
                <div class="stat-label">Total Victims</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['cases_open'] ?? 0; ?></div>
                <div class="stat-label">Open Cases</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_appointments']; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_doctors']; ?></div>
                <div class="stat-label">Total Doctors</div>
            </div>
        </div>

        <div class="report-section">
            <h3>Case Status Distribution</h3>
            <div class="chart-placeholder">
                <div style="text-align: center;">
                    <p><strong>Chart visualization coming soon</strong></p>
                    <p style="margin-top: 10px;">
                        Open: <?php echo $stats['cases_open'] ?? 0; ?> | 
                        In-Process: <?php echo $stats['cases_in_process'] ?? 0; ?> | 
                        Closed: <?php echo $stats['cases_closed'] ?? 0; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="report-section">
            <h3>Monthly Trends</h3>
            <div class="chart-placeholder">
                Chart visualization coming soon
            </div>
        </div>
    </div>
</body>
</html>
