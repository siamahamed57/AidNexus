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

$success_msg = '';
$error_msg = '';

// Fetch victim profile
$sql = "SELECT * FROM victim WHERE victim_id = :victim_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":victim_id", $victim_id);
oci_execute($stmt);
$victim = oci_fetch_assoc($stmt);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contact_info = sanitize_input($_POST['contact_info']);
    $address = sanitize_input($_POST['address']);
    
    $sql = "UPDATE victim SET contact_info = :contact_info, address = :address WHERE victim_id = :victim_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":contact_info", $contact_info);
    oci_bind_by_name($stmt, ":address", $address);
    oci_bind_by_name($stmt, ":victim_id", $victim_id);
    
    if (oci_execute($stmt)) {
        $success_msg = 'Profile updated successfully!';
        // Refresh victim data
        $sql = "SELECT * FROM victim WHERE victim_id = :victim_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":victim_id", $victim_id);
        oci_execute($stmt);
        $victim = oci_fetch_assoc($stmt);
    } else {
        $error = oci_error($stmt);
        $error_msg = 'Update failed: ' . $error['message'];
    }
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AidNexus</title>
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
        .profile-container { background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow); max-width: 800px; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
        .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background-color: var(--primary-green); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; }
        .profile-info h3 { font-size: 1.5rem; color: var(--text-dark); margin-bottom: 5px; }
        .profile-info p { color: var(--text-light); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        .form-group input, .form-group textarea { padding: 12px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 0.9rem; }
        .form-group input:disabled { background-color: #f5f5f5; color: var(--text-light); }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-green); }
        .btn { padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 1rem; }
        .btn-primary { background-color: var(--primary-green); color: white; }
        .btn-primary:hover { background-color: #45a049; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <?php include 'includes/victim_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>My Profile</h2>
        </div>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr($victim['VICTIM_NAME'], 0, 2)); ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($victim['VICTIM_NAME']); ?></h3>
                    <p>Victim ID: <?php echo $victim_id; ?></p>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($victim['VICTIM_NAME']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="text" value="<?php echo format_date($victim['DOB']); ?>" disabled>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <input type="text" value="<?php echo htmlspecialchars($victim['GENDER']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Contact Information *</label>
                        <input type="text" name="contact_info" value="<?php echo htmlspecialchars($victim['CONTACT_INFO']); ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Address *</label>
                    <textarea name="address" rows="3" required><?php echo htmlspecialchars($victim['ADDRESS']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>
