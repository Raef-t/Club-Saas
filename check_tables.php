<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=db_clubs", "root", "");
    $tables = ['tenants', 'people', 'authentication_users', 'personal_access_tokens'];
    foreach ($tables as $table) {
        $res = $pdo->query("SHOW TABLES LIKE '$table'");
        echo "Table [$table]: " . ($res->rowCount() > 0 ? "EXISTS" : "MISSING") . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
