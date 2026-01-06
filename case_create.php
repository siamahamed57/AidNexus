<?php
session_start();
include 'db.php';
include 'includes/session_check.php';
include 'includes/role_check.php';
include 'includes/functions.php';

require_admin();

$success_msg = '';
$error_msg = '';

// Fetch all victims for dropdown
$victims = [];
$sql = "SELECT victim_id, victim_name FROM victim WHERE is_active = 1 ORDER BY victim_name";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $victims[] = $row;
}

// Fetch all doctors for dropdown
$doctors = [];
$sql = "SELECT doctor_id, doctor_name FROM doctor ORDER BY doctor_name";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $doctors[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $victim_id = intval($_POST['victim_id']);
    $case_type = sanitize_input($_POST['case_type']);
    $description = sanitize_input($_POST['description']);
    $assigned_doctor_id = !empty($_POST['assigned_doctor_id']) ? intval($_POST['assigned_doctor_id']) : null;
    $case_status = 'Open';
    
    $sql = "INSERT INTO case_t (case_id, victim_id, case_type, case_status, open_date, description, assigned_doctor_id) 
            VALUES (case_seq.NEXTVAL, :victim_id, :case_type, :case_status, SYSDATE, :description, :assigned_doctor_id)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":victim_id", $victim_id);
    oci_bind_by_name($stmt, ":case_type", $case_type);
    oci_bind_by_name($stmt, ":case_status", $case_status);
    oci_bind_by_name($stmt, ":description", $description);
    oci_bind_by_name($stmt, ":assigned_doctor_id", $assigned_doctor_id);
    
    if (oci_execute($stmt)) {
        header('Location: all_cases.php');
        exit;
    } else {
        $error = oci_error($stmt);
        $error_msg = 'Failed to create case: ' . $error['message'];
    }
}

$user_initials = get_user_initials($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Case - AidNexus</title>
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
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow); max-width: 800px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { padding: 12px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-green); }
        .btn { padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 1rem; }
        .btn-primary { background-color: var(--primary-green); color: white; }
        .btn-primary:hover { background-color: #45a049; }
        .btn-secondary { background-color: var(--text-light); color: white; margin-left: 10px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Create New Case</h2>
        </div>

        <div class="form-container">
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Victim *</label>
                        <select name="victim_id" required>
                            <option value="">Choose a victim...</option>
                            <?php foreach ($victims as $victim): ?>
                                <option value="<?php echo $victim['VICTIM_ID']; ?>"><?php echo htmlspecialchars($victim['VICTIM_NAME']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Case Type *</label>
                        <select name="case_type" required>
                            <option value="">Select type...</option>
                            <option value="Medical Treatment Support">Medical Treatment Support</option>
                            <option value="Legal Assistance">Legal Assistance</option>
                            <option value="Financial Aid">Financial Aid</option>
                            <option value="Counseling Services">Counseling Services</option>
                            <option value="Emergency Shelter">Emergency Shelter</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Assign Doctor (Optional)</label>
                    <select name="assigned_doctor_id">
                        <option value="">No doctor assigned</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['DOCTOR_ID']; ?>"><?php echo htmlspecialchars($doctor['DOCTOR_NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Case Description</label>
                    <textarea name="description" rows="4" placeholder="Enter case details..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Create Case</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='all_cases.php'">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>
