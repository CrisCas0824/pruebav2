<?php
/**
 * Script de Instalación / Migración Automática de la Base de Datos
 * Ejecutable vía navegador: http://localhost/.../database/import_db.php
 * o vía CLI: php database/import_db.php
 */

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$sqlFile = __DIR__ . '/database.sql';

echo "<h2>🔧 Asistente de Instalación de Base de Datos - La Caserita ERP</h2>";

if (!file_exists($sqlFile)) {
    die("<p style='color:red;'>❌ Error: No se encontró el archivo SQL en: {$sqlFile}</p>");
}

try {
    // 1. Conexión al servidor MySQL de XAMPP
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green;'>✔️ Conexión con el servidor MySQL (XAMPP) establecida con éxito.</p>";

    // 2. Leer archivo SQL
    $sql = file_get_contents($sqlFile);

    // 3. Ejecutar script multi-query
    $pdo->exec($sql);

    echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:8px; border:1px solid #c3e6cb; margin-top:15px;'>";
    echo "<h3>🎉 ¡Base de datos instalada y poblada exitosamente!</h3>";
    echo "<ul>";
    echo "<li><strong>Base de datos:</strong> <code>lacaserita_db</code></li>";
    echo "<li><strong>Tablas creadas:</strong> usuario, producto, inventario, kardex, proveedor, reposicion, sesion_caja, movimiento_caja, pedido, detalle_pedido, pago_verificacion, envio_delivery, metodo_pago, categoria, etc.</li>";
    echo "<li><strong>Usuarios de prueba creados (Password: 123456):</strong>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@lacaserita.com</li>";
    echo "<li><strong>Cajero:</strong> cajero@lacaserita.com</li>";
    echo "<li><strong>Motorizado:</strong> repartidor@lacaserita.com</li>";
    echo "<li><strong>Cliente:</strong> cliente@gmail.com</li>";
    echo "</ul>";
    echo "</li>";
    echo "</ul>";
    echo "<p><a href='../index.php?action=login' style='display:inline-block; background:#ff7f00; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>🚀 Ir al Sistema</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; border:1px solid #f5c6cb;'>";
    echo "<h3>❌ Error al ejecutar el script SQL</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
