<?php
$conn = oci_connect('root','root','localhost/XE');

if (!$conn) {
    $e = oci_error();
    die("Oracle connection failed: " . $e['message']);
}

?>
