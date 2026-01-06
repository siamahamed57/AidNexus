<?php
session_start();
include 'db.php'; // Oracle connection

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orgname = trim($_POST['orgname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!isset($_POST['terms'])) {
        $error = "You must accept the Data Security Policy!";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $sql_check = "SELECT * FROM users WHERE email=:email";
        $stmt_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stmt_check, ":email", $email);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);

        if ($row) {
            $error = "Email already registered!";
        } else {
            // Insert new user
            $sql_insert = "INSERT INTO users (user_id, username, email, password_hash) 
                           VALUES (users_seq.NEXTVAL, :username, :email, :password_hash)";
            $stmt_insert = oci_parse($conn, $sql_insert);
            oci_bind_by_name($stmt_insert, ":username", $orgname);
            oci_bind_by_name($stmt_insert, ":email", $email);
            oci_bind_by_name($stmt_insert, ":password_hash", $password_hash);

            if (oci_execute($stmt_insert)) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AidNexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Copy your previous CSS exactly */
        :root { --primary: #435663; --primary-hover: #313647; --accent-green: #A3B087; --bg-warm: #FFF8D4; --text-dark: #313647; --text-light: #64748b; --white: #ffffff; --input-bg: #f8fafc; --border: #e2e8f0; --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;color:var(--text-dark);background-color:var(--bg-warm);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;}
        .page-header{text-align:center;margin-bottom:30px;}
        .brand-name{font-size:1.8rem;font-weight:800;color:var(--primary);margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:10px;}
        .page-header p{color:var(--text-light);font-size:1rem;font-weight:500;}
        .registration-card{background-color:var(--white);width:100%;max-width:480px;border-radius:12px;padding:40px;box-shadow:var(--shadow-lg);border:1px solid var(--border);}
        .card-header{text-align:center;margin-bottom:30px;}
        .card-header h2{font-size:1.5rem;color:var(--primary-hover);font-weight:700;margin-bottom:8px;}
        .card-header p{color:var(--text-light);font-size:0.9rem;}
        .form-group{margin-bottom:20px;}
        label{display:block;font-size:0.9rem;font-weight:600;color:var(--text-dark);margin-bottom:8px;}
        input{width:100%;padding:12px 16px;background-color:var(--input-bg);border:1px solid var(--border);border-radius:6px;font-family:'Inter',sans-serif;font-size:0.95rem;color:var(--text-dark);transition:all 0.2s ease;}
        input:focus{outline:none;border-color:var(--primary);background-color:var(--white);box-shadow:0 0 0 3px rgba(67,86,99,0.1);}
        input::placeholder{color:#94a3b8;}
        .checkbox-wrapper{display:flex;align-items:center;gap:10px;margin-top:24px;margin-bottom:24px;}
        input[type="checkbox"]{width:18px;height:18px;accent-color:var(--primary);margin:0;}
        .checkbox-label{font-size:0.9rem;color:var(--text-dark);font-weight:500;margin-bottom:0;}
        .link-accent{color:var(--accent-green);text-decoration:none;font-weight:600;}
        .link-accent:hover{text-decoration:underline;}
        .btn-submit{width:100%;background-color:var(--primary);color:var(--white);border:none;padding:14px;font-size:1rem;font-weight:600;border-radius:6px;cursor:pointer;transition:background-color 0.2s;}
        .btn-submit:hover{background-color:var(--primary-hover);}
        .card-footer{text-align:center;margin-top:24px;font-size:0.9rem;color:var(--text-light);}
        @media(max-width:500px){.registration-card{padding:24px;}}
    </style>
</head>
<body>

    <div class="page-header">
        <div class="brand-name">AidNexus</div>
        <p>Humanitarian Network Access</p>
    </div>

    <div class="registration-card">
        <div class="card-header">
            <h2>Establish Your Profile</h2>
            <p>Secure your access to shared resources and coordination tools.</p>
        </div>

        <!-- Display PHP Messages -->
        <?php if($error) echo "<p style='color:red;text-align:center;margin-bottom:15px;'>$error</p>"; ?>
        <?php if($success) echo "<p style='color:green;text-align:center;margin-bottom:15px;'>$success</p>"; ?>

        <form method="post">
            <div class="form-group">
                <label for="orgname">Organization Name</label>
                <input type="text" id="orgname" name="orgname" placeholder="Global Relief Agency" required>
            </div>
            <div class="form-group">
                <label for="email">Primary Contact Email</label>
                <input type="email" id="email" name="email" placeholder="contact@globalrelief.org" required>
            </div>
            <div class="form-group">
                <label for="phone">Support Hotline Number</label>
                <input type="tel" id="phone" name="phone" placeholder="+1 (555) 987-6543" required>
            </div>
            <div class="form-group">
                <label for="password">Create Secure Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
            </div>

            <div class="checkbox-wrapper">
                <input type="checkbox" id="terms" name="terms">
                <label for="terms" class="checkbox-label">
                    I confirm acceptance of the <a href="#" class="link-accent">Data Security Policy</a>
                </label>
            </div>

            <button type="submit" class="btn-submit">Register Organization</button>

            <div class="card-footer">
                Returning user? <a href="login.php" class="link-accent">Sign in to your dashboard</a>
            </div>
        </form>
    </div>

</body>
</html>
