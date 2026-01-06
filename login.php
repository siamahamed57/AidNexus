<?php
session_start();
include 'db.php';
include 'includes/functions.php';

$error = '';
$success_msg = '';

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Handle logout message
if (isset($_GET['logout'])) {
    $success_msg = 'You have been successfully logged out.';
}

// Handle timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Query database for user with role and victim_id
        $sql = "SELECT user_id, username, email, password_hash, role, victim_id FROM users WHERE email = :email";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":email", $email);
        oci_execute($stmt);
        
        $user = oci_fetch_assoc($stmt);
        
        if ($user && password_verify($password, $user['PASSWORD_HASH'])) {
            // Login successful
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['email'] = $user['EMAIL'];
            $_SESSION['role'] = $user['ROLE'] ?? 'victim'; // Default to victim if no role
            $_SESSION['victim_id'] = $user['VICTIM_ID'];
            $_SESSION['last_activity'] = time();
            
            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                // In production, store this token in database
            }
            
            // Redirect to appropriate dashboard based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: victim_dashboard.php");
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AidNexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #435663;
            --primary-hover: #313647;
            --accent-green: #A3B087;
            --bg-warm: #FFF8D4;
            --text-dark: #313647;
            --text-light: #64748b;
            --white: #ffffff;
            --input-bg: #f8fafc;
            --border: #e2e8f0;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-warm); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-name {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
        }

        .login-card {
            background-color: var(--white);
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: var(--primary-hover); 
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(67, 86, 99, 0.1);
        }

        input::placeholder {
            color: #94a3b8;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }

        .link-accent {
            color: var(--accent-green); 
            text-decoration: none;
            font-weight: 600;
        }

        .link-accent:hover {
            text-decoration: underline;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary); 
            margin: 0; 
        }

        .remember-me label {
            margin-bottom: 0;
            font-weight: 500;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .card-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        @media (max-width: 500px) {
            .login-card {
                padding: 24px;
            }
            .form-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="brand-name">
            AidNexus
        </div>
        <p>Access the Humanitarian Logistics Hub</p>
    </div>

    <div class="login-card">
        
        <div class="card-header">
            <h2>Welcome Back</h2>
            <p>Enter your credentials to manage your relief operations.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="email@organization.org" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-actions">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="link-accent">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-submit">Log In to AidNexus</button>

            <div class="card-footer">
                Don't have an account? <a href="registration.php" class="link-accent">Register your organization here</a>
            </div>
        </form>
    </div>

</body>
</html>
