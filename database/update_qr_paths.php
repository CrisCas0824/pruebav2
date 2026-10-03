<?php
require_once __DIR__ . '/../Core/dataBase.php';

try {
    $db = new DataBase();
    $conn = $db->getConnection();
    $conn->exec("UPDATE metodo_pago SET codigo_qr = 'uploads/qr/qr_yape.svg', requiere_comprobante = 1 WHERE LOWER(nombre) LIKE '%yape%'");
    $conn->exec("UPDATE metodo_pago SET codigo_qr = 'uploads/qr/qr_plin.svg', requiere_comprobante = 1 WHERE LOWER(nombre) LIKE '%plin%'");
    $conn->exec("UPDATE metodo_pago SET requiere_comprobante = 1 WHERE LOWER(nombre) LIKE '%transferencia%'");
    echo "QR PATHS UPDATED SUCCESSFULLY\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
