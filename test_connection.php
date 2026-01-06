<?php
/**
 * Oracle Database Connection Test Script
 * Tests the connection and displays database information
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Connection Test - AidNexus</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #435663; border-bottom: 3px solid #435663; padding-bottom: 10px; }
        h2 { color: #313647; margin-top: 30px; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #ef4444; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #3b82f6; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; color: #374151; }
        tr:hover { background: #f9fafb; }
        .code { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔌 AidNexus Database Connection Test</h1>";

// Test 1: Check if Oracle extension is loaded
echo "<h2>1. PHP Oracle Extension Check</h2>";
if (function_exists('oci_connect')) {
    echo "<div class='success'>✓ Oracle OCI extension is loaded and available</div>";
} else {
    echo "<div class='error'>✗ Oracle OCI extension is NOT loaded. Please enable it in php.ini</div>";
    echo "<div class='info'><strong>Fix:</strong> Uncomment <code>extension=oci8_12c</code> in your php.ini file and restart Apache</div>";
    echo "</div></body></html>";
    exit;
}

// Test 2: Attempt database connection
echo "<h2>2. Database Connection Test</h2>";
echo "<div class='code'>";
echo "Host: localhost/XE<br>";
echo "Username: root<br>";
echo "Password: ****<br>";
echo "</div>";

$conn = oci_connect('root', 'root', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    echo "<div class='error'>";
    echo "<strong>✗ Connection Failed!</strong><br><br>";
    echo "<strong>Error Code:</strong> " . htmlspecialchars($e['code']) . "<br>";
    echo "<strong>Error Message:</strong> " . htmlspecialchars($e['message']) . "<br>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>Common Solutions:</strong><br>";
    echo "1. Make sure Oracle XE is running<br>";
    echo "2. Verify username and password are correct<br>";
    echo "3. Check if the service name 'XE' is correct<br>";
    echo "4. Ensure Oracle listener is running (run: <code>lsnrctl status</code>)<br>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='success'>✓ Successfully connected to Oracle Database!</div>";

// Test 3: List all tables
echo "<h2>3. Database Tables</h2>";
$sql = "SELECT table_name FROM user_tables ORDER BY table_name";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

echo "<table>";
echo "<tr><th>#</th><th>Table Name</th><th>Row Count</th><th>Status</th></tr>";

$tableCount = 0;
while ($row = oci_fetch_assoc($stmt)) {
    $tableCount++;
    $tableName = $row['TABLE_NAME'];
    
    // Get row count for each table
    $countSql = "SELECT COUNT(*) as cnt FROM " . $tableName;
    $countStmt = oci_parse($conn, $countSql);
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $rowCount = $countRow['CNT'];
    
    $statusBadge = $rowCount > 0 ? "<span class='badge badge-success'>Has Data</span>" : "<span class='badge badge-error'>Empty</span>";
    
    echo "<tr>";
    echo "<td>" . $tableCount . "</td>";
    echo "<td><strong>" . htmlspecialchars($tableName) . "</strong></td>";
    echo "<td>" . $rowCount . " rows</td>";
    echo "<td>" . $statusBadge . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($tableCount == 0) {
    echo "<div class='error'>No tables found! Please run the db.sql script to create tables.</div>";
}

// Test 4: List all sequences
echo "<h2>4. Database Sequences</h2>";
$sql = "SELECT sequence_name, last_number FROM user_sequences ORDER BY sequence_name";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

echo "<table>";
echo "<tr><th>#</th><th>Sequence Name</th><th>Current Value</th></tr>";

$seqCount = 0;
while ($row = oci_fetch_assoc($stmt)) {
    $seqCount++;
    echo "<tr>";
    echo "<td>" . $seqCount . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['SEQUENCE_NAME']) . "</strong></td>";
    echo "<td>" . $row['LAST_NUMBER'] . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($seqCount == 0) {
    echo "<div class='error'>No sequences found! Sequences are required for auto-incrementing IDs.</div>";
}

// Test 5: Check specific required tables
echo "<h2>5. Required Tables Check</h2>";
$requiredTables = ['USERS', 'VICTIM', 'DOCTOR', 'CASE_T', 'APPOINTMENT', 'ROLES'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $sql = "SELECT COUNT(*) as cnt FROM user_tables WHERE table_name = :tablename";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":tablename", $table);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    
    if ($row['CNT'] == 0) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "<div class='success'>✓ All required tables exist!</div>";
} else {
    echo "<div class='error'>✗ Missing tables: " . implode(', ', $missingTables) . "</div>";
}

// Test 6: Test a simple query
echo "<h2>6. Sample Query Test</h2>";
$sql = "SELECT COUNT(*) as user_count FROM users";
$stmt = oci_parse($conn, $sql);

if (oci_execute($stmt)) {
    $row = oci_fetch_assoc($stmt);
    echo "<div class='success'>✓ Query executed successfully!<br>";
    echo "Total users in database: <strong>" . $row['USER_COUNT'] . "</strong></div>";
} else {
    $e = oci_error($stmt);
    echo "<div class='error'>✗ Query failed: " . htmlspecialchars($e['message']) . "</div>";
}

// Summary
echo "<h2>📊 Connection Summary</h2>";
echo "<div class='info'>";
echo "<strong>Status:</strong> Database connection is working properly!<br>";
echo "<strong>Tables Found:</strong> " . $tableCount . "<br>";
echo "<strong>Sequences Found:</strong> " . $seqCount . "<br>";
echo "<strong>Next Steps:</strong> You can now proceed with the application development.<br>";
echo "</div>";

oci_close($conn);

echo "</div></body></html>";
?>
