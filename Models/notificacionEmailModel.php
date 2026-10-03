<?php
require_once __DIR__ . '/../Core/dataBase.php';

/**
 * Modelo de Notificaciones por Correo Electrónico
 * Diseñado según la plantilla del video de referencia:
 * - Cabecera con banner/logo y datos de contacto de la tienda.
 * - Resumen del pedido (#, total, fecha, método de pago).
 * - Tabla con los productos comprados (imágenes, nombres, cantidades, subtotales).
 * - Botón de llamado a la acción: "VER ESTADO DE MI PEDIDO".
 * - Soporte para envío mediante mail() o SMTP con simulación en entorno local.
 */
class NotificacionEmailModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    /**
     * Obtiene la configuración activa de correo de la tienda
     */
    public function obtenerConfiguracion() {
        $query = "SELECT * FROM configuracion_correo WHERE activo = 1 ORDER BY id_config DESC LIMIT 1";
        $stmt = $this->conn->query($query);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            $config = [
                'nombre_empresa'       => 'Tienda La Caserita',
                'logo_url'             => 'Images/logo.png',
                'cabecera_correo_url'  => 'Images/abarrotes-banner.png',
                'telefono_whatsapp'    => '+51 987654321',
                'correo_soporte'       => 'pedidos@lacaserita.com',
                'enlace_seguimiento'   => 'index.php?action=mi_cuenta',
                'direccion_fisica'     => 'Av. Central 450, Lima - Perú',
                'color_primario'       => '#15803D',
                'color_secundario'     => '#ff7f00',
                'smtp_host'            => null,
                'smtp_port'            => 587,
                'smtp_user'            => null,
                'smtp_pass'            => null
            ];
        }

        return $config;
    }

    /**
     * Guarda o actualiza los ajustes de correo y cabecera
     */
    public function guardarConfiguracion($datos) {
        $query = "UPDATE configuracion_correo SET
                    nombre_empresa = :nombre,
                    logo_url = :logo,
                    cabecera_correo_url = :cabecera,
                    telefono_whatsapp = :whatsapp,
                    correo_soporte = :correo,
                    enlace_seguimiento = :seguimiento,
                    direccion_fisica = :direccion,
                    color_primario = :c_primario,
                    color_secundario = :c_secundario,
                    smtp_host = :host,
                    smtp_port = :port,
                    smtp_user = :user,
                    smtp_pass = :pass
                  WHERE id_config = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':nombre'       => $datos['nombre_empresa'] ?? 'Tienda La Caserita',
            ':logo'         => $datos['logo_url'] ?? 'Images/logo.png',
            ':cabecera'     => $datos['cabecera_correo_url'] ?? '',
            ':whatsapp'     => $datos['telefono_whatsapp'] ?? '',
            ':correo'       => $datos['correo_soporte'] ?? '',
            ':seguimiento'  => $datos['enlace_seguimiento'] ?? 'index.php?action=mi_cuenta',
            ':direccion'    => $datos['direccion_fisica'] ?? '',
            ':c_primario'   => $datos['color_primario'] ?? '#15803D',
            ':c_secundario' => $datos['color_secundario'] ?? '#ff7f00',
            ':host'         => $datos['smtp_host'] ?? null,
            ':port'         => (int)($datos['smtp_port'] ?? 587),
            ':user'         => $datos['smtp_user'] ?? null,
            ':pass'         => $datos['smtp_pass'] ?? null,
            ':id'           => (int)($datos['id_config'] ?? 1)
        ]);
    }

    /**
     * Construye la plantilla HTML del correo para la confirmación de la compra
     * Basado exactamente en el diseño mostrado en el video de referencia
     */
    public function generarHtmlConfirmacionPedido($pedido, $detalles, $cliente) {
        $cfg = $this->obtenerConfiguracion();

        $empresaNombre    = htmlspecialchars($cfg['nombre_empresa']);
        $colorPrimario    = '#15803d'; // Verde oscuro La Caserita
        $colorSecundario  = '#22c55e'; // Verde claro La Caserita
        $whatsapp         = htmlspecialchars($cfg['telefono_whatsapp']);
        $correoSoporte    = htmlspecialchars($cfg['correo_soporte']);
        $enlaceRastreo    = htmlspecialchars($cfg['enlace_seguimiento']);
        $direccionFisica  = htmlspecialchars($cfg['direccion_fisica']);

        $idPad            = str_pad((int)($pedido['id_pedido'] ?? 0), 5, '0', STR_PAD_LEFT);
        $codigoPedido     = htmlspecialchars($idPad);
        $totalPedido      = number_format((float)($pedido['total'] ?? 0), 2);
        $fechaPedido      = !empty($pedido['fecha']) ? date('d/m/Y H:i', strtotime($pedido['fecha'])) : date('d/m/Y H:i');
        $metodoPago       = htmlspecialchars($pedido['metodo_pago'] ?? 'Pago Electrónico');
        $estadoPedido     = htmlspecialchars($pedido['estado'] ?? 'Pendiente');
        $nombreCliente    = htmlspecialchars($cliente['nombre'] ?? 'Estimado(a) Cliente');

        // Construir tabla de productos
        $filasProductos = '';
        foreach ($detalles as $item) {
            $nombreProd = htmlspecialchars($item['nombre'] ?? $item['titulo'] ?? 'Producto');
            $cantProd   = (int)($item['cantidad'] ?? 1);
            $precioProd = number_format((float)($item['precio_unitario'] ?? $item['precio'] ?? 0), 2);
            $subtProd   = number_format((float)($item['subtotal'] ?? 0), 2);
            $imgUrl     = !empty($item['imagen']) ? $item['imagen'] : (!empty($item['imagen_url']) ? $item['imagen_url'] : 'Images/logo.png');

            $filasProductos .= "
            <tr style='border-bottom: 1px solid #e2e8f0;'>
                <td style='padding: 12px 8px; width: 60px;'>
                    <img src='{$imgUrl}' alt='{$nombreProd}' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #edf2f7;'>
                </td>
                <td style='padding: 12px 8px; font-family: sans-serif; font-size: 14px; color: #1e293b;'>
                    <strong>{$nombreProd}</strong><br>
                    <span style='color: #64748b; font-size: 12px;'>Cant: {$cantProd} &times; S/ {$precioProd}</span>
                </td>
                <td style='padding: 12px 8px; font-family: sans-serif; font-size: 14px; font-weight: 700; color: #0f172a; text-align: right;'>
                    S/ {$subtProd}
                </td>
            </tr>";
        }

        // Cabecera con banner o título estilizado
        $cabeceraHtml = "";
        if (!empty($cfg['cabecera_correo_url'])) {
            $cabeceraHtml = "
            <tr>
                <td style='padding: 0; text-align: center; background-color: {$colorPrimario};'>
                    <img src='{$cfg['cabecera_correo_url']}' alt='{$empresaNombre}' style='display: block; width: 100%; max-width: 600px; height: auto; margin: 0 auto;'>
                </td>
            </tr>";
        } else {
            $cabeceraHtml = "
            <tr>
                <td style='padding: 24px; text-align: center; background-color: {$colorPrimario}; color: #ffffff;'>
                    <h1 style='margin: 0; font-family: sans-serif; font-size: 24px; letter-spacing: 1px;'>{$empresaNombre}</h1>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Tu tienda de confianza a tu puerta</p>
                </td>
            </tr>";
        }

        // HTML final completo
        return "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Confirmación de Pedido - {$empresaNombre}</title>
</head>
<body style='margin: 0; padding: 20px 0; background-color: #f1f5f9; font-family: Arial, sans-serif;'>
    <table role='presentation' cellspacing='0' cellpadding='0' border='0' align='center' width='100%' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06);'>
        {$cabeceraHtml}

        <!-- Contenido Central -->
        <tr>
            <td style='padding: 28px 24px;'>
                <p style='margin: 0 0 8px 0; font-size: 15px; color: #475569;'>
                    ⏰ <strong>Estado de tu orden:</strong> <span style='display: inline-block; padding: 3px 10px; border-radius: 12px; background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 12px;'>{$estadoPedido}</span>
                </p>
                <h2 style='margin: 12px 0 6px 0; font-size: 20px; color: #0f172a;'>
                    Hola {$nombreCliente},
                </h2>
                <p style='margin: 0 0 20px 0; font-size: 14px; color: #475569; line-height: 1.5;'>
                    ¡Gracias por tu compra! Tu pedido <strong>#{$codigoPedido}</strong> ha sido recibido exitosamente en el sistema de {$empresaNombre}.
                </p>

                <!-- Botón de Acción Principal -->
                <div style='text-align: center; margin: 24px 0;'>
                    <a href='{$enlaceRastreo}' target='_blank' style='display: inline-block; background-color: {$colorSecundario}; color: #ffffff; text-decoration: none; font-weight: bold; font-size: 14px; padding: 13px 28px; border-radius: 8px; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(255,127,0,0.3);'>
                        VER ESTADO DE MI PEDIDO
                    </a>
                </div>

                <!-- Tarjeta Resumen del Pedido (Idéntico al video) -->
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='margin: 20px 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
                    <tr style='background-color: {$colorSecundario}; color: #ffffff;'>
                        <th colspan='2' style='padding: 10px 14px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left;'>
                            Resumen del Pedido
                        </th>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 10px 14px; font-size: 13px; color: #64748b;'>Pedido:</td>
                        <td style='padding: 10px 14px; font-size: 14px; font-weight: bold; color: #0f172a; text-align: right;'>#{$codigoPedido}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 10px 14px; font-size: 13px; color: #64748b;'>Total:</td>
                        <td style='padding: 10px 14px; font-size: 16px; font-weight: 800; color: {$colorPrimario}; text-align: right;'>S/ {$totalPedido}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 10px 14px; font-size: 13px; color: #64748b;'>Fecha:</td>
                        <td style='padding: 10px 14px; font-size: 13px; color: #334155; text-align: right;'>{$fechaPedido}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 14px; font-size: 13px; color: #64748b;'>Método de Pago:</td>
                        <td style='padding: 10px 14px; font-size: 13px; font-weight: 600; color: #334155; text-align: right;'>{$metodoPago}</td>
                    </tr>
                </table>

                <!-- Detalle de Productos -->
                <h3 style='margin: 24px 0 12px 0; font-size: 15px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;'>
                    Artículos en tu orden:
                </h3>
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                    {$filasProductos}
                </table>

                <!-- Contacto & Ayuda -->
                <div style='margin-top: 24px; padding: 14px; background-color: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; font-size: 12px; color: #64748b; line-height: 1.5;'>
                    ¿Tienes alguna duda sobre tu compra? Escríbenos a nuestro WhatsApp oficial: <strong>{$whatsapp}</strong> o responde a este correo (<strong>{$correoSoporte}</strong>).
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style='padding: 20px; background-color: #0f172a; text-align: center; color: #94a3b8; font-size: 12px;'>
                <p style='margin: 0 0 6px 0; font-weight: 600; color: #ffffff;'>{$empresaNombre}</p>
                <p style='margin: 0 0 8px 0;'>{$direccionFisica}</p>
                <p style='margin: 0; font-size: 11px; color: #64748b;'>Este correo fue generado automáticamente para notificar la compra de tu pedido.</p>
            </td>
        </tr>
    </table>
</body>
</html>";
    }

    /**
     * Envía la confirmación del pedido al correo del cliente
     */
    public function enviarConfirmacionCompra($idPedido) {
        // 1. Obtener datos del pedido
        $queryPed = "SELECT p.*, u.correo, u.nombre as user_nombre, u.apellido as user_apellido 
                     FROM pedido p 
                     JOIN usuario u ON p.id_usuario = u.id_usuario 
                     WHERE p.id_pedido = :id";
        $stmtPed = $this->conn->prepare($queryPed);
        $stmtPed->bindParam(':id', $idPedido, PDO::PARAM_INT);
        $stmtPed->execute();
        $pedido = $stmtPed->fetch(PDO::FETCH_ASSOC);

        if (!$pedido || empty($pedido['correo'])) {
            return ['ok' => false, 'mensaje' => 'Pedido o correo del destinatario no encontrado.'];
        }

        // 2. Obtener productos del pedido
        $queryDet = "SELECT d.*, pr.nombre, pr.titulo, pr.imagen, pr.imagen_url 
                     FROM detalle_pedido d 
                     JOIN producto pr ON d.id_producto = pr.id_producto 
                     WHERE d.id_pedido = :id";
        $stmtDet = $this->conn->prepare($queryDet);
        $stmtDet->bindParam(':id', $idPedido, PDO::PARAM_INT);
        $stmtDet->execute();
        $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $cliente = [
            'nombre' => !empty($pedido['cliente_nombre']) ? $pedido['cliente_nombre'] : trim($pedido['user_nombre'] . ' ' . $pedido['user_apellido']),
            'correo' => $pedido['correo']
        ];

        // 3. Generar cuerpo HTML del correo
        $htmlContenido = $this->generarHtmlConfirmacionPedido($pedido, $detalles, $cliente);
        $idPad = str_pad((int)$idPedido, 5, '0', STR_PAD_LEFT);
        $asunto = "Confirmación de Pedido #" . $idPad . " - " . ($cfg['nombre_empresa'] ?? 'La Caserita');

        // 4. Intentar envío con mail() nativo o registrar en log para entorno local
        $destinatario = $pedido['correo'];
        $cfg = $this->obtenerConfiguracion();
        $remitente = !empty($cfg['correo_soporte']) ? $cfg['correo_soporte'] : 'no-reply@lacaserita.com';

        $enviado = false;
        try {
            require_once __DIR__ . '/MailHelper.php';
            $enviado = MailHelper::sendEmail($destinatario, $asunto, $htmlContenido, true);
        } catch (Exception $e) {
            $enviado = false;
        }

        // En entornos locales (XAMPP sin sendmail activo), guardar una copia en archivo local para verificación visual
        $backupDir = __DIR__ . '/../scratch/emails_enviados';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/pedido_' . $idPedido . '_' . time() . '.html';
        @file_put_contents($backupFile, $htmlContenido);

        return [
            'ok'          => true,
            'mensaje'     => 'Correo de confirmación procesado exitosamente.',
            'enviado'     => $enviado,
            'destinatario'=> $destinatario,
            'archivo_log' => $backupFile
        ];
    }
}
?>
