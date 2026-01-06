<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_admin();

$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch case details with all related information
$sql = "SELECT c.*, v.victim_name, v.contact_info, v.address, d.doctor_name, d.specialization
        FROM case_t c
        LEFT JOIN victim v ON c.victim_id = v.victim_id
        LEFT JOIN doctor d ON c.assigned_doctor_id = d.doctor_id
        WHERE c.case_id = :case_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":case_id", $case_id);
oci_execute($stmt);
$case = oci_fetch_assoc($stmt);

if (!$case) {
    die('Case not found');
}

// Fetch appointments for this case
$appointments = [];
$sql = "SELECT * FROM appointment WHERE case_id = :case_id ORDER BY appt_datetime DESC";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":case_id", $case_id);
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
    <title>Case Details - AidNexus</title>
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
        .details-container { background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow); margin-bottom: 20px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .detail-item { margin-bottom: 15px; }
        .detail-label { font-size: 0.85rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; margin-bottom: 5px; }
        .detail-value { font-size: 1rem; color: var(--text-dark); }
        .status-badge { padding: 6px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .status-open { background-color: #dbeafe; color: #1e40af; }
        .status-inprocess { background-color: #fef3c7; color: #92400e; }
        .status-closed { background-color: #d1fae5; color: #065f46; }
        .section-title { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--border); }
        .appointment-list { margin-top: 15px; }
        .appointment-item { padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Case Details #CS-<?php echo str_pad($case_id, 4, '0', STR_PAD_LEFT); ?></h2>
            <button class="btn btn-primary" onclick="window.location.href='case_edit.php?id=<?php echo $case_id; ?>'">Edit Case</button>
        </div>

        <div class="details-container">
            <h3 class="section-title">Case Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Case ID</div>
                    <div class="detail-value">CS-<?php echo str_pad($case_id, 4, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo strtolower(str_replace('-', '', $case['CASE_STATUS'])); ?>">
                            <?php echo htmlspecialchars($case['CASE_STATUS']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Case Type</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['CASE_TYPE']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date Filed</div>
                    <div class="detail-value"><?php echo format_date($case['OPEN_DATE']); ?></div>
                </div>
                <?php if ($case['CLOSE_DATE']): ?>
                <div class="detail-item">
                    <div class="detail-label">Date Closed</div>
                    <div class="detail-value"><?php echo format_date($case['CLOSE_DATE']); ?></div>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <div class="detail-label">Assigned Doctor</div>
                    <div class="detail-value"><?php echo $case['DOCTOR_NAME'] ? htmlspecialchars($case['DOCTOR_NAME']) : '<em>Not assigned</em>'; ?></div>
                </div>
            </div>
            
            <?php if ($case['DESCRIPTION']): ?>
            <div class="detail-item">
                <div class="detail-label">Description</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($case['DESCRIPTION'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="details-container">
            <h3 class="section-title">Victim Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['VICTIM_NAME']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['CONTACT_INFO']); ?></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['ADDRESS']); ?></div>
                </div>
            </div>
        </div>

        <div class="details-container">
            <h3 class="section-title">Appointments (<?php echo count($appointments); ?>)</h3>
            <?php if (empty($appointments)): ?>
                <p style="color: var(--text-light);">No appointments scheduled for this case.</p>
            <?php else: ?>
                <div class="appointment-list">
                    <?php foreach ($appointments as $appt): ?>
                        <div class="appointment-item">
                            <strong><?php echo format_datetime($appt['APPT_DATETIME']); ?></strong><br>
                            Location: <?php echo htmlspecialchars($appt['LOCATION']); ?><br>
                            Status: <span class="status-badge status-<?php echo strtolower($appt['STATUS']); ?>"><?php echo htmlspecialchars($appt['STATUS']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <button class="btn btn-primary" onclick="window.location.href='all_cases.php'">← Back to All Cases</button>
    </div>
</body>
</html>
