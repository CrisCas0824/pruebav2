<?php
require_once __DIR__ . '/../Core/dataBase.php';

try {
    $db = (new DataBase())->getConnection();

    echo "--- Actualizando tabla distrito_delivery ---\n";

    // Asegurar estructura
    $db->exec("CREATE TABLE IF NOT EXISTS distrito_delivery (
        id_distrito INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        costo_delivery DECIMAL(10,2) NOT NULL DEFAULT 5.00,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        orden INT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("ALTER TABLE distrito_delivery CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("ALTER TABLE direccion CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Desactivar distritos mayores a 5
    $db->exec("UPDATE distrito_delivery SET activo = 0 WHERE id_distrito > 5");

    // Actualizar o insertar los 5 distritos exactos de Chiclayo
    $distritos = [
        ['id' => 1, 'nombre' => 'Chiclayo Centro', 'costo' => 5.00, 'orden' => 1],
        ['id' => 2, 'nombre' => 'La Victoria', 'costo' => 7.00, 'orden' => 2],
        ['id' => 3, 'nombre' => 'José Leonardo Ortiz', 'costo' => 8.00, 'orden' => 3],
        ['id' => 4, 'nombre' => 'Pimentel', 'costo' => 12.00, 'orden' => 4],
        ['id' => 5, 'nombre' => 'Santa Victoria', 'costo' => 6.00, 'orden' => 5],
    ];

    $stmtUpsert = $db->prepare("INSERT INTO distrito_delivery (id_distrito, nombre, costo_delivery, activo, orden)
        VALUES (:id, :nombre, :costo, 1, :orden)
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), costo_delivery = VALUES(costo_delivery), activo = 1, orden = VALUES(orden)");

    foreach ($distritos as $d) {
        $stmtUpsert->execute([
            ':id' => $d['id'],
            ':nombre' => $d['nombre'],
            ':costo' => $d['costo'],
            ':orden' => $d['orden']
        ]);
    }

    echo "Distritos actualizados exitosamente:\n";
    $active = $db->query("SELECT id_distrito, nombre, costo_delivery, activo, orden FROM distrito_delivery WHERE activo = 1 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
    print_r($active);

    // Agregar columnas de horario y fecha_programada a pedido si no existen
    $colsPedido = $db->query("SHOW COLUMNS FROM pedido")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('horario_entrega', $colsPedido)) {
        $db->exec("ALTER TABLE pedido ADD COLUMN horario_entrega VARCHAR(60) NULL DEFAULT 'Lo antes posible (30-45 min)' AFTER tipo_entrega");
        echo "Columna horario_entrega agregada a pedido.\n";
    } else {
        echo "Columna horario_entrega ya existe.\n";
    }

    if (!in_array('fecha_programada', $colsPedido)) {
        $db->exec("ALTER TABLE pedido ADD COLUMN fecha_programada DATETIME NULL AFTER horario_entrega");
        echo "Columna fecha_programada agregada a pedido.\n";
    } else {
        echo "Columna fecha_programada ya existe.\n";
    }

    echo "Actualización de base de datos finalizada con éxito.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
