<?php
$mysqli = new mysqli("localhost", "root", "", "hospital_bengo");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

if (!$mysqli->query("ALTER TABLE utilizadores ADD COLUMN sexo ENUM('M','F') NOT NULL DEFAULT 'M' AFTER nome")) {
    echo "Table alteration failed: (" . $mysqli->errno . ") " . $mysqli->error;
} else {
    echo "Success.";
}

$mysqli->close();
