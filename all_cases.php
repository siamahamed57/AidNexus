<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_admin();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build SQL query
$sql = "SELECT c.case_id, c.case_type, c.case_status, c.open_date, c.close_date, c.description,
        v.victim_name, d.doctor_name, c.assigned_doctor_id
        FROM case_t c
        LEFT JOIN victim v ON c.victim_id = v.victim_id
        LEFT JOIN doctor d ON c.assigned_doctor_id = d.doctor_id
        WHERE 1=1";
$params = [];

if ($status_filter != 'all') {
    $sql .= " AND c.case_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (c.case_type LIKE :search OR v.victim_name LIKE :search OR CAST(c.case_id AS VARCHAR2(20)) LIKE :search)";
    $search_param = '%' . $search_query . '%';
    $params[':search'] = $search_param;
}

$sql .= " ORDER BY c.open_date DESC";

$stmt = oci_parse($conn, $sql);
foreach ($params as $key => $value) {
    oci_bind_by_name($stmt, $key, $params[$key]);
}
oci_execute($stmt);

$cases = [];
while ($row = oci_fetch_assoc($stmt)) {
    $cases[] = $row;
}

// Count cases by status
$status_counts = ['all' => 0, 'Open' => 0, 'In-Process' => 0, 'Closed' => 0];
$count_sql = "SELECT case_status, COUNT(*) as cnt FROM case_t GROUP BY case_status";
$count_stmt = oci_parse($conn, $count_sql);
oci_execute($count_stmt);
while ($row = oci_fetch_assoc($count_stmt)) {
    $status_counts[$row['CASE_STATUS']] = $row['CNT'];
    $status_counts['all'] += $row['CNT'];
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Cases - AidNexus</title>
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
        .filters { display: flex; gap: 15px; margin-bottom: 20px; }
        .filters input, .filters select { padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; }
        .data-table { background: white; border-radius: 8px; box-shadow: var(--shadow); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #F8F9FA; }
        th { padding: 15px 20px; text-align: left; font-size: 0.85rem; font-weight: 600; color: var(--text-dark); text-transform: uppercase; }
        td { padding: 15px 20px; border-top: 1px solid var(--border); font-size: 0.9rem; }
        tr:hover { background-color: #F8F9FA; }
        .status-badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-open { background-color: #dbeafe; color: #1e40af; }
        .status-inprocess { background-color: #fef3c7; color: #92400e; }
        .status-closed { background-color: #d1fae5; color: #065f46; }
        .action-btn { color: var(--primary-green); font-weight: 600; cursor: pointer; margin-right: 10px; }
        .action-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>
            <h1>AidNexus</h1>
            <span class="admin-badge">ADMIN</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item" onclick="window.location.href='admin_dashboard.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </div>
            <div class="nav-item" onclick="window.location.href='intake.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                <span>Intake (Register)</span>
            </div>
            <div class="nav-item" onclick="window.location.href='all_victims.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>All Victims</span>
            </div>
            <div class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>All Cases</span>
            </div>
            <div class="nav-item" onclick="window.location.href='all_doctors.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>All Doctors</span>
            </div>
            <div class="nav-item" onclick="window.location.href='all_appointments.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>All Appointments</span>
            </div>
            <div class="nav-item" onclick="window.location.href='reports.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Reports & Analysis</span>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="nav-item" onclick="window.location.href='logout.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2>All Cases</h2>
            <button class="btn btn-primary" onclick="window.location.href='case_create.php'">+ Create New Case</button>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-grow: 1;">
                <input type="text" name="search" placeholder="Search by Case ID, Type, or Victim..." value="<?php echo htmlspecialchars($search_query); ?>" style="flex-grow: 1;">
                <select name="status">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Open" <?php echo $status_filter == 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="In-Process" <?php echo $status_filter == 'In-Process' ? 'selected' : ''; ?>>In-Process</option>
                    <option value="Closed" <?php echo $status_filter == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Victim</th>
                        <th>Case Type</th>
                        <th>Assigned Doctor</th>
                        <th>Status</th>
                        <th>Date Filed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-light);">No cases found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <td><strong>CS-<?php echo str_pad($case['CASE_ID'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo htmlspecialchars($case['VICTIM_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($case['CASE_TYPE']); ?></td>
                                <td><?php echo $case['DOCTOR_NAME'] ? htmlspecialchars($case['DOCTOR_NAME']) : '<em>Unassigned</em>'; ?></td>
                                <td><span class="status-badge status-<?php echo strtolower(str_replace('-', '', $case['CASE_STATUS'])); ?>"><?php echo htmlspecialchars($case['CASE_STATUS']); ?></span></td>
                                <td><?php echo format_date($case['OPEN_DATE']); ?></td>
                                <td>
                                    <a href="case_edit.php?id=<?php echo $case['CASE_ID']; ?>" class="action-btn">Edit</a>
                                    <a href="case_details.php?id=<?php echo $case['CASE_ID']; ?>" class="action-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
