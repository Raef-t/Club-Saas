<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=db_clubs", "root", "");
$res = $pdo->query("DESCRIBE people");
foreach($res as $row) {
    echo $row['Field'] . "\n";
}
