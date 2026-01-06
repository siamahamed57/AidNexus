<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_admin();

// Fetch all appointments with case and victim info
$appointments = [];
$sql = "SELECT a.*, c.case_type, v.victim_name, d.doctor_name
        FROM appointment a
        LEFT JOIN case_t c ON a.case_id = c.case_id
        LEFT JOIN victim v ON c.victim_id = v.victim_id
        LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
        ORDER BY a.appt_datetime DESC";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $appointments[] = $row;
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - AidNexus</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h2 { font-size: 1.75rem; color: var(--text-dark); font-weight: 700; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-primary { background-color: var(--primary-green); color: white; }
        .btn-primary:hover { background-color: #45a049; }
        .data-table { background: white; border-radius: 8px; box-shadow: var(--shadow); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #F8F9FA; }
        th { padding: 15px 20px; text-align: left; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); text-transform: uppercase; }
        td { padding: 15px 20px; border-top: 1px solid var(--border); font-size: 0.9rem; }
        tr:hover { background-color: #F8F9FA; }
        .status-badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-scheduled { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>All Appointments</h2>
            <button class="btn btn-primary" onclick="alert('Appointment scheduling coming soon!')">+ Schedule Appointment</button>
        </div>

        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Victim</th>
                        <th>Case Type</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-light);">No appointments scheduled yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><strong>APT-<?php echo str_pad($appt['APPOINTMENT_ID'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo htmlspecialchars($appt['VICTIM_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($appt['CASE_TYPE']); ?></td>
                                <td><?php echo htmlspecialchars($appt['DOCTOR_NAME']); ?></td>
                                <td><?php echo format_datetime($appt['APPT_DATETIME']); ?></td>
                                <td><?php echo htmlspecialchars($appt['LOCATION']); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($appt['STATUS']); ?>"><?php echo htmlspecialchars($appt['STATUS']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
