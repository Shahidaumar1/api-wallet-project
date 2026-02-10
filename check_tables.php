<?php
$db = new PDO('sqlite:database/database.sqlite');
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = $result->fetchAll(PDO::FETCH_ASSOC);

echo "========== DATABASE TABLES ==========\n\n";

if (empty($tables)) {
    echo "❌ NO TABLES FOUND!\n";
} else {
    echo "✅ Tables found: " . count($tables) . "\n\n";
    foreach($tables as $table) {
        echo "  📦 " . $table['name'] . "\n";
        
        // Show columns for each table
        $cols = $db->query("PRAGMA table_info(" . $table['name'] . ")")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $col) {
            echo "     - {$col['name']} ({$col['type']})\n";
        }
    }
}

echo "\n=====================================\n";
?>
