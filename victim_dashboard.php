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

// Handle file upload (placeholder - would need proper implementation)
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    // File upload logic would go here
    $upload_message = 'Document upload functionality coming soon!';
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
    <title>All Documents - AidNexus</title>
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

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 1.75rem;
            color: var(--text-dark);
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--text-dark);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .user-email {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 30px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 30px;
        }

        .tab {
            padding: 12px 0;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }

        .tab.active {
            color: var(--text-dark);
            border-bottom-color: var(--text-dark);
        }

        .tab:hover {
            color: var(--text-dark);
        }

        /* Upload Section */
        .upload-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .upload-section h3 {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .upload-form {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .upload-btn {
            padding: 10px 24px;
            background-color: var(--text-dark);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-btn:hover {
            background-color: var(--sidebar-bg);
        }

        /* Documents Table */
        .documents-table {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #F8F9FA;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-top: 1px solid var(--border);
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        tr:hover {
            background-color: #F8F9FA;
        }

        .doc-name {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #3498DB;
            font-weight: 500;
        }

        .doc-name svg {
            width: 16px;
            height: 16px;
        }

        .action-icon {
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.2s;
        }

        .action-icon:hover {
            color: var(--text-dark);
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-light);
        }

        @media (max-width: 1024px) {
            .upload-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'includes/victim_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>All Documents</h2>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name">MD. Ibrahim Khalil</div>
                    <div class="user-email">@ victim</div>
                </div>
                <div class="user-avatar"><?php echo $user_initials; ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active">All Documents</div>
            <div class="tab">Medical</div>
            <div class="tab">Legal</div>
            <div class="tab">Financial</div>
            <div class="tab">Consent</div>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h3>Upload New Document</h3>
            <?php if ($upload_message): ?>
                <div style="padding: 10px; background: #E8F5E9; color: #2E7D32; border-radius: 6px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($upload_message); ?>
                </div>
            <?php endif; ?>
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="doc_type">
                        <option value="medical">Medical</option>
                        <option value="legal">Legal</option>
                        <option value="financial">Financial</option>
                        <option value="consent">Consent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select File</label>
                    <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.png">
                </div>
                <button type="submit" class="upload-btn">Upload</button>
            </form>
        </div>

        <!-- Documents Table -->
        <div class="documents-table">
            <table>
                <thead>
                    <tr>
                        <th>DOCUMENT NAME</th>
                        <th>TYPE</th>
                        <th>RELATED CASE</th>
                        <th>UPLOADED BY</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="doc-name">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                Consent-Form.pdf
                            </div>
                        </td>
                        <td>Consent</td>
                        <td>Medical Treatment Support</td>
                        <td>Admin User</td>
                        <td>
                            <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </td>
                    </tr>
                    <!-- More rows would be dynamically generated from database -->
                </tbody>
            </table>
            <div class="empty-state" style="display: none;">
                <p>No documents uploaded yet. Upload your first document above.</p>
            </div>
        </div>
    </div>

</body>
</html>
