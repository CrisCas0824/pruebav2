<?php
require_once __DIR__ . '/../Core/dataBase.php';

try {
    $db = (new DataBase())->getConnection();

    // Crear tabla venta si se requiere para compatibilidad con vistas viejas
    $db->exec("CREATE TABLE IF NOT EXISTS venta (
        id_venta INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NOT NULL,
        id_usuario INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL,
        tipo_comprobante VARCHAR(30) NOT NULL DEFAULT 'boleta',
        numero_comprobante VARCHAR(50) NOT NULL,
        estado_pago VARCHAR(50) NOT NULL DEFAULT 'pendiente',
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_venta_pedido (id_pedido)
    ) ENGINE=InnoDB;");

    // Crear directorio de uploads de vouchers y productos si no existen
    if (!is_dir(__DIR__ . '/../uploads/vouchers')) {
        mkdir(__DIR__ . '/../uploads/vouchers', 0777, true);
    }
    if (!is_dir(__DIR__ . '/../uploads/qr')) {
        mkdir(__DIR__ . '/../uploads/qr', 0777, true);
    }
    if (!is_dir(__DIR__ . '/../uploads/productos')) {
        mkdir(__DIR__ . '/../uploads/productos', 0777, true);
    }

    echo "Tabla venta asegurada y directorios de uploads creados.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
