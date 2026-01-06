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

// Handle form submission for new case
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_case') {
    $case_type = sanitize_input($_POST['case_type']);
    $case_status = 'Open';
    $victim_id = isset($_POST['victim_id']) ? intval($_POST['victim_id']) : 1; // Default victim for demo
    
    $sql = "INSERT INTO case_t (case_id, case_type, case_status, open_date, victim_id) 
            VALUES (case_seq.NEXTVAL, :case_type, :case_status, SYSDATE, :victim_id)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":case_type", $case_type);
    oci_bind_by_name($stmt, ":case_status", $case_status);
    oci_bind_by_name($stmt, ":victim_id", $victim_id);
    
    if (oci_execute($stmt)) {
        redirect_with_message('case.php', 'Case created successfully!', 'success');
    } else {
        $error = 'Failed to create case.';
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build SQL query based on filters
$sql = "SELECT case_id, case_type, case_status, open_date, close_date FROM case_t WHERE victim_id = :victim_id";
$params = [':victim_id' => $victim_id];

if ($status_filter != 'all') {
    $sql .= " AND case_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (case_type LIKE :search OR CAST(case_id AS VARCHAR2(20)) LIKE :search)";
    $search_param = '%' . $search_query . '%';
    $params[':search'] = $search_param;
}

$sql .= " ORDER BY open_date DESC";

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
$count_sql = "SELECT case_status, COUNT(*) as cnt FROM case_t WHERE victim_id = :victim_id GROUP BY case_status";
$count_stmt = oci_parse($conn, $count_sql);
oci_bind_by_name($count_stmt, ":victim_id", $victim_id);
oci_execute($count_stmt);
while ($row = oci_fetch_assoc($count_stmt)) {
    $status_counts[$row['CASE_STATUS']] = $row['CNT'];
    $status_counts['all'] += $row['CNT'];
}

$user_initials = get_user_initials($username);
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cases - AidNexus</title>
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
            
            --status-open: #435663;      
            --status-inprogress: #3b82f6;
            --status-closed: #38a169;
            --status-pending: #f6ad55;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            display: flex;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            border: 1px solid var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .dashboard-layout {
            display: flex;
            width: 100%;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header svg {
            width: 24px;
            height: 24px;
        }

        .sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sidebar-nav {
            flex-grow: 1;
            padding: 20px 0;
        }

        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
            color: white;
        }

        .nav-item.active {
            background-color: var(--primary-green);
            color: white;
            border-left: 4px solid white;
        }

        .nav-item svg {
            width: 18px;
            height: 18px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }


        
        .main-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 30px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .main-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-hover);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .tab-navigation {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 25px;
            padding-left: 20px;
        }

        .tab-button {
            padding: 12px 20px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
        }

        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-button:hover:not(.active) {
            color: var(--text-dark);
        }

        .case-controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 0 20px;
        }

        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filters input[type="text"], .filters select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-dark);
            background-color: var(--white);
            min-width: 150px;
        }

        .case-table-container {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--border);
            margin: 0 20px;
        }

        .case-table-header {
            display: grid;
            grid-template-columns: 1fr 2fr 1.5fr 1fr 0.5fr;
            padding: 12px 20px;
            background-color: #f0f4f8;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        .case-table-row {
            display: grid;
            grid-template-columns: 1fr 2fr 1.5fr 1fr 0.5fr;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            align-items: center;
            transition: background-color 0.1s;
        }
        
        .case-table-row:hover {
            background-color: #f8fafc;
        }

        .case-table-row:last-child {
            border-bottom: none;
        }

        .case-id {
            font-weight: 600;
            color: var(--primary);
        }

        .case-title {
            font-weight: 500;
        }

        .case-status-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
        }

        .status-open {
            background-color: #dbeafe;
            color: var(--status-open);
        }

        .status-inprogress {
            background-color: #eff6ff;
            color: var(--status-inprogress);
        }

        .status-closed {
            background-color: #d1fae5;
            color: var(--status-closed);
        }
        
        .action-link {
            color: var(--accent-green);
            font-weight: 600;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .dashboard-layout {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid var(--border);
                padding: 15px 0;
            }
            .nav-menu ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
            }
            .nav-item a {
                padding: 8px 15px;
                border-right: none !important;
                border-bottom: 3px solid transparent;
            }
            .nav-item.active a {
                border-right: none !important;
                border-bottom: 3px solid var(--primary);
            }
            .logo-section, .sidebar-footer {
                display: none;
            }
            .main-content {
                padding: 20px 0;
            }
            .case-controls-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            .filters {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .filters input, .filters select {
                min-width: 100%;
            }
            .case-table-header, .case-table-row {
                grid-template-columns: 1fr 2fr 1fr;
            }
            .case-table-header div:nth-child(4), .case-table-row div:nth-child(4),
            .case-table-header div:nth-child(5), .case-table-row div:nth-child(5) {
                display: none;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/victim_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>My Cases</h1>
                <div class="user-profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-light);"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <div class="avatar"><?php echo $user_initials; ?></div>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
            <?php endif; ?>

            <div class="tab-navigation">
                <button class="tab-button <?php echo $status_filter == 'all' ? 'active' : ''; ?>" onclick="window.location.href='case.php?status=all'">
                    All Cases (<?php echo $status_counts['all']; ?>)
                </button>
                <button class="tab-button <?php echo $status_filter == 'In-Process' ? 'active' : ''; ?>" onclick="window.location.href='case.php?status=In-Process'">
                    In Process Cases (<?php echo $status_counts['In-Process']; ?>)
                </button>
                <button class="tab-button <?php echo $status_filter == 'Open' ? 'active' : ''; ?>" onclick="window.location.href='case.php?status=Open'">
                    Open Cases (<?php echo $status_counts['Open']; ?>)
                </button>
                <button class="tab-button <?php echo $status_filter == 'Closed' ? 'active' : ''; ?>" onclick="window.location.href='case.php?status=Closed'">
                    Closed Cases (<?php echo $status_counts['Closed']; ?>)
                </button>
            </div>
            
            <div class="case-controls-bar">
                <form class="filters" method="GET" action="case.php">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <input type="text" name="search" placeholder="Search by Case ID or Type..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px;">Search</button>
                </form>
                <button class="btn btn-primary" onclick="alert('Create new case form would open here')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Request New Assistance
                </button>
            </div>

            <div class="case-table-container">
                <div class="case-table-header">
                    <div>Case ID</div>
                    <div>Assistance Type</div>
                    <div>Status</div>
                    <div>Date Filed</div>
                    <div style="text-align: right;">Action</div>
                </div>
                
                <?php if (empty($cases)): ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-light);">
                        No cases found matching your criteria.
                    </div>
                <?php else: ?>
                    <?php foreach ($cases as $case): ?>
                        <div class="case-table-row">
                            <div class="case-id">VC-<?php echo str_pad($case['CASE_ID'], 4, '0', STR_PAD_LEFT); ?></div>
                            <div class="case-title"><?php echo htmlspecialchars($case['CASE_TYPE']); ?></div>
                            <div>
                                <span class="case-status-tag status-<?php echo strtolower(str_replace('-', '', $case['CASE_STATUS'])); ?>">
                                    <?php echo htmlspecialchars($case['CASE_STATUS']); ?>
                                </span>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-light);"><?php echo format_date($case['OPEN_DATE']); ?></div>
                            <div style="text-align: right;"><a href="#" class="action-link">View Details</a></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
    </main>

</body>
</html>
