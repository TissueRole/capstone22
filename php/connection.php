<?php
    // Hide all errors from public view (security)
    error_reporting(0);
    ini_set('display_errors', 0);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "capstone";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if($conn->connect_error){
        die("Connection Failed". $conn->connect_error);
    }
?>