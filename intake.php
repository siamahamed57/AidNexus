<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

// Require admin access
require_admin();

$success_msg = '';
$error_msg = '';

// Handle victim registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $victim_name = sanitize_input($_POST['victim_name']);
    $dob = sanitize_input($_POST['dob']);
    $gender = sanitize_input($_POST['gender']);
    $contact_info = sanitize_input($_POST['contact_info']);
    $address = sanitize_input($_POST['address']);
    
    // Insert victim
    $sql = "INSERT INTO victim (victim_id, victim_name, dob, gender, contact_info, address) 
            VALUES (victim_seq.NEXTVAL, :victim_name, TO_DATE(:dob, 'YYYY-MM-DD'), :gender, :contact_info, :address)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":victim_name", $victim_name);
    oci_bind_by_name($stmt, ":dob", $dob);
    oci_bind_by_name($stmt, ":gender", $gender);
    oci_bind_by_name($stmt, ":contact_info", $contact_info);
    oci_bind_by_name($stmt, ":address", $address);
    
    if (oci_execute($stmt)) {
        $success_msg = 'Victim registered successfully!';
    } else {
        $error = oci_error($stmt);
        $error_msg = 'Registration failed: ' . $error['message'];
    }
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intake (Register) - AidNexus</title>
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
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow); max-width: 800px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { padding: 12px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-green); }
        .submit-btn { padding: 12px 30px; background-color: var(--primary-green); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; }
        .submit-btn:hover { background-color: #45a049; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
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
            <div class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                <span>Intake (Register)</span>
            </div>
            <div class="nav-item" onclick="window.location.href='all_victims.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>All Victims</span>
            </div>
            <div class="nav-item" onclick="window.location.href='all_cases.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>All Cases</span>
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
            <h2>Intake New Victim</h2>
        </div>

        <div class="form-container">
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="victim_name" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Information *</label>
                        <input type="text" name="contact_info" placeholder="+1-555-1234" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Address *</label>
                    <textarea name="address" rows="3" required></textarea>
                </div>

                <button type="submit" class="submit-btn">Register Victim</button>
            </form>
        </div>
    </div>
</body>
</html>
