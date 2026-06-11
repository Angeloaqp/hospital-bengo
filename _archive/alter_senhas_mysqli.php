<?php
$mysqli = new mysqli("localhost", "root", "", "hospital_bengo");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$res = $mysqli->query("DESCRIBE senhas");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'codigo') {
        echo "Before: " . $row['Type'] . "\n";
    }
}

if (!$mysqli->query("ALTER TABLE senhas MODIFY codigo VARCHAR(50) NOT NULL")) {
    echo "Table alteration failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

$res = $mysqli->query("DESCRIBE senhas");
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'codigo') {
        echo "After: " . $row['Type'] . "\n";
    }
}
$mysqli->close();
