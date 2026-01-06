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
    die('Error: No victim record linked to your account.');
}

// Handle form submission for new appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_appointment') {
    $service_type = sanitize_input($_POST['service_type']);
    $preferred_date = sanitize_input($_POST['preferred_date']);
    $preferred_time = sanitize_input($_POST['preferred_time']);
    $notes = sanitize_input($_POST['notes']);
    $location = "To Be Determined";
    $status = "Pending";
    
    // Convert date to Oracle format
    $appt_datetime = $preferred_date . ' ' . ($preferred_time == 'am' ? '10:00:00' : '14:00:00');
    
    $sql = "INSERT INTO appointment (appointment_id, appt_datetime, location, status, case_id, doctor_id) 
            VALUES (appointment_seq.NEXTVAL, TO_DATE(:appt_datetime, 'YYYY-MM-DD HH24:MI:SS'), :location, :status, NULL, NULL)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":appt_datetime", $appt_datetime);
    oci_bind_by_name($stmt, ":location", $location);
    oci_bind_by_name($stmt, ":status", $status);
    
    if (oci_execute($stmt)) {
        redirect_with_message('appointment.php', 'Appointment request submitted successfully!', 'success');
    } else {
        $error = 'Failed to create appointment.';
    }
}

// Fetch upcoming appointments
$upcoming_appointments = [];
$sql = "SELECT a.appointment_id, a.appt_datetime, a.location, a.status, c.case_type 
        FROM appointment a 
        LEFT JOIN case_t c ON a.case_id = c.case_id 
        WHERE a.appt_datetime >= SYSDATE 
        ORDER BY a.appt_datetime ASC";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $upcoming_appointments[] = $row;
}

$user_initials = get_user_initials($username);
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - AidNexus</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #435663;
            --primary-hover: #313647;
            --accent-green: #A3B087;
            --bg-page: #f8fafc;
            --bg-sidebar: #ffffff;
            --text-dark: #313647;      
            --text-light: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            
            --status-confirmed: #38a169;
            --status-pending: #f6ad55;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-page);
            min-height: 100vh;
            display: flex;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            border: 1px solid var(--primary);
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
            background-color: #2C3E50;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .logo-section {
            padding: 0 24px 24px 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .logo {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .nav-menu {
            flex-grow: 1;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: var(--text-light);
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            stroke-width: 2;
        }
        
        .nav-item.active a {
            background-color: #f0f4f8;
            color: var(--primary);
            border-right: 3px solid var(--primary);
            font-weight: 600;
        }

        .nav-item a:not(.active):hover {
            background-color: #f8fafc;
            color: var(--text-dark);
        }

        .sidebar-footer {
            padding: 0 24px;
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .main-content {
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

        .list-panel {
            background-color: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .list-panel h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-hover);
            margin-bottom: 20px;
        }

        .appointment-item {
            padding: 15px 0;
            border-bottom: 1px dashed var(--border);
        }
        
        .appointment-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-details strong {
            display: block;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .item-details span {
            font-size: 0.9rem;
            color: var(--text-light);
            display: block;
            margin-top: 4px;
        }

        .item-actions {
            margin-top: 10px;
        }

        .item-actions button {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-dark);
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
            transition: all 0.2s;
        }

        .item-actions button:hover {
            border-color: var(--accent-green);
            color: var(--accent-green);
        }
        
        .status-tag {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
            text-transform: uppercase;
        }

        .tag-confirmed {
            background-color: #d1fae5;
            color: var(--status-confirmed);
        }

        .tag-pending {
            background-color: #fef3c7;
            color: var(--status-pending);
        }
        
        .request-form-panel {
            background-color: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-top: 30px;
        }
        
        .request-form-panel h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-hover);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-dark);
            background-color: #f8fafc;
            transition: border-color 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background-color: var(--white);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
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
                padding: 20px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <?php include 'includes/victim_sidebar.php'; ?>

        <main class="main-content">
            <header class="main-header">
                <h1>Appointment Scheduling</h1>
                <div class="user-profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-light);"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <div class="avatar"><?php echo $user_initials; ?></div>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
            <?php endif; ?>

            <div class="list-panel">
                <h2>Upcoming Appointments</h2>
                
                <?php if (empty($upcoming_appointments)): ?>
                    <p style="color: var(--text-light); text-align: center; padding: 20px;">No upcoming appointments scheduled.</p>
                <?php else: ?>
                    <?php foreach ($upcoming_appointments as $appt): ?>
                        <div class="appointment-item">
                            <div class="item-details">
                                <strong><?php echo htmlspecialchars($appt['CASE_TYPE'] ?? 'General Appointment'); ?> 
                                    <span class="status-tag tag-<?php echo strtolower($appt['STATUS']); ?>"><?php echo htmlspecialchars($appt['STATUS']); ?></span>
                                </strong>
                                <span>Date: <?php echo format_datetime($appt['APPT_DATETIME']); ?></span>
                                <span>Location: <?php echo htmlspecialchars($appt['LOCATION']); ?></span>
                            </div>
                            <div class="item-actions">
                                <button>View Details</button>
                                <button>Reschedule</button>
                                <button>Cancel</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="request-form-panel">
                <h2>Request a New Meeting or Service</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_appointment">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service-type">Type of Assistance Needed</label>
                            <select id="service-type" name="service_type" required>
                                <option value="">Select a service...</option>
                                <option value="medical">Medical Checkup / Aid</option>
                                <option value="legal">Legal Consultation</option>
                                <option value="housing">Housing/Shelter Aid</option>
                                <option value="financial">Financial Assistance Inquiry</option>
                                <option value="other">Other Inquiry</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="case-id">Related Case File ID (Optional)</label>
                            <input type="text" id="case-id" name="case_id" placeholder="e.g., VC-2025-007">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred-date">Preferred Date</label>
                            <input type="date" id="preferred-date" name="preferred_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="preferred-time">Preferred Time Slot</label>
                            <select id="preferred-time" name="preferred_time" required>
                                <option value="">Select Time...</option>
                                <option value="am">Morning (9:00 - 12:00)</option>
                                <option value="pm">Afternoon (1:00 - 4:00)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Reason for Appointment / Notes</label>
                        <textarea id="notes" name="notes" placeholder="Briefly describe the help you need or the issue you are facing." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                        Submit Request
                    </button>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
