<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = config("database.default");
$conn = config("database.connections.".$db);
echo "default={$db}\n";
echo "driver=".($conn["driver"] ?? "")."\n";
echo "database=".($conn["database"] ?? "")."\n";
echo "host=".($conn["host"] ?? "")."\n";
if (($conn["driver"] ?? "") === "mysql") {
    $pdo = DB::connection()->getPdo();
    foreach (["healthinpocket_users", "persons", "hospitals"] as $table) {
        echo "\n===== {$table} =====\n";
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo ($row["Create Table"] ?? json_encode($row))."\n";
        $cols = $pdo->query("SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, COLUMN_KEY, IS_NULLABLE, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND COLUMN_NAME = 'id'");
        while ($c = $cols->fetch(PDO::FETCH_ASSOC)) {
            echo "id_column=".json_encode($c)."\n";
        }
        $t = $pdo->query("SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'")->fetch(PDO::FETCH_ASSOC);
        echo "table_meta=".json_encode($t)."\n";
    }
} else {
    echo "Not mysql; skipping SHOW CREATE TABLE.\n";
}
