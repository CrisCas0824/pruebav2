<?php
require_once __DIR__ . '/../Core/dataBase.php';

/**
 * Modelo para Simulación de Celular como Lector Portátil de Códigos de Barras
 * y Búsqueda Flexible de Productos (por Código de Barras, SKU o Nombre) en el POS.
 */
class PosScannerModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    /**
     * Busca y resuelve un producto de manera inteligente:
     * 1. Por código de barras exacto (escaneado por la cámara del celular)
     * 2. Por código SKU interno (ej: 'ARR-001')
     * 3. Por ID numérico directo
     * 4. Por coincidencia en el nombre / título (ej: 'arroz', 'leche', 'primor')
     */
    public function resolverProducto($criterio) {
        $criterio = trim($criterio ?? '');
        if ($criterio === '') {
            return null;
        }

        // 1. Intento por código de barras o SKU exacto
        $query = "SELECT p.*, COALESCE(i.stock_actual, p.stock, 0) as stock_disponible 
                  FROM producto p 
                  LEFT JOIN inventario i ON p.id_producto = i.id_producto 
                  WHERE p.activo = 1 
                    AND (p.codigo_barras = :crit1 OR p.codigo = :crit2)
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':crit1', $criterio);
        $stmt->bindParam(':crit2', $criterio);
        $stmt->execute();
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $prod['metodo_coincidencia'] = 'codigo_barras_o_sku';
            return $prod;
        }

        // 2. Si es numérico, probar por ID de producto
        if (is_numeric($criterio)) {
            $queryId = "SELECT p.*, COALESCE(i.stock_actual, p.stock, 0) as stock_disponible 
                        FROM producto p 
                        LEFT JOIN inventario i ON p.id_producto = i.id_producto 
                        WHERE p.activo = 1 AND p.id_producto = :id LIMIT 1";
            $stmtId = $this->conn->prepare($queryId);
            $idNum = (int)$criterio;
            $stmtId->bindParam(':id', $idNum, PDO::PARAM_INT);
            $stmtId->execute();
            $prodId = $stmtId->fetch(PDO::FETCH_ASSOC);
            if ($prodId) {
                $prodId['metodo_coincidencia'] = 'id_producto';
                return $prodId;
            }
        }

        // 3. Intento flexible por Nombre / Título
        $like = '%' . $criterio . '%';
        $queryNombre = "SELECT p.*, COALESCE(i.stock_actual, p.stock, 0) as stock_disponible 
                        FROM producto p 
                        LEFT JOIN inventario i ON p.id_producto = i.id_producto 
                        WHERE p.activo = 1 
                          AND (p.nombre LIKE :like1 OR p.titulo LIKE :like2 OR p.descripcion LIKE :like3)
                        ORDER BY p.nombre ASC 
                        LIMIT 1";
        $stmtNombre = $this->conn->prepare($queryNombre);
        $stmtNombre->bindParam(':like1', $like);
        $stmtNombre->bindParam(':like2', $like);
        $stmtNombre->bindParam(':like3', $like);
        $stmtNombre->execute();
        $prodNombre = $stmtNombre->fetch(PDO::FETCH_ASSOC);

        if ($prodNombre) {
            $prodNombre['metodo_coincidencia'] = 'nombre';
            return $prodNombre;
        }

        return null;
    }

    public function registrarEscaneoRemoto($token, $codigo, $dispositivo = 'Celular Móvil') {
        $sesion = $this->conn->prepare("SELECT 1 FROM sesion_caja WHERE token_emparejamiento = :token AND estado = 'abierta' LIMIT 1");
        $sesion->bindParam(':token', $token);
        $sesion->execute();
        if (!$sesion->fetchColumn()) {
            return ['ok' => false, 'mensaje' => 'La caja está cerrada o la sesión ya no es válida.'];
        }

        $insert = $this->conn->prepare("INSERT INTO pos_escaner_cola
            (token_sesion, codigo_escaneado, nombre_producto, id_producto, cantidad, procesado, dispositivo)
            VALUES (:token, :codigo, 'Escaneo remoto', NULL, 1, 0, :dispositivo)");
        $insert->bindParam(':token', $token);
        $insert->bindParam(':codigo', $codigo);
        $insert->bindParam(':dispositivo', $dispositivo);
        $insert->execute();

        return ['ok' => true, 'codigo' => $codigo, 'mensaje' => 'Escaneo enviado a la PC'];
    }

    /**
     * Registra un escaneo desde el celular emparejado:
     * - Resuelve el producto (por código o nombre)
     * - Inserta el evento en la cola de escaneos (pos_escaner_cola)
     * - Actualiza el carrito en la sesión de caja (sesion_caja)
     */
    public function procesarEscaneoMovil($token, $criterio, $cantidad = 1, $dispositivo = 'Celular Móvil') {
        // Verificar que la sesión de caja exista y esté abierta
        $querySes = "SELECT * FROM sesion_caja WHERE token_emparejamiento = :token LIMIT 1";
        $stmtSes = $this->conn->prepare($querySes);
        $stmtSes->bindParam(':token', $token);
        $stmtSes->execute();
        $sesion = $stmtSes->fetch(PDO::FETCH_ASSOC);

        if (!$sesion) {
            // Si no hay sesión de caja abierta, permitir el escaneo para Inventario y Compras
            $insertCola = "INSERT INTO pos_escaner_cola 
                             (token_sesion, codigo_escaneado, nombre_producto, id_producto, cantidad, procesado, dispositivo) 
                           VALUES 
                             (:token, :codigo, 'Escaneo Remoto ERP', NULL, :cant, 0, :disp)";
            $stmtCola = $this->conn->prepare($insertCola);
            $stmtCola->bindParam(':token', $token);
            $stmtCola->bindParam(':codigo', $criterio);
            $stmtCola->bindParam(':cant', $cantidad, PDO::PARAM_INT);
            $stmtCola->bindParam(':disp', $dispositivo);
            $stmtCola->execute();

            return [
                'ok'      => true,
                'codigo'  => $criterio,
                'mensaje' => "Código '{$criterio}' enviado al sistema."
            ];
        }

        // Resolver producto
        $producto = $this->resolverProducto($criterio);
        if (!$producto) {
            // Guardar en cola de escaneo para que Inventario o Compras puedan capturar el código leído
            $insertCola = "INSERT INTO pos_escaner_cola 
                             (token_sesion, codigo_escaneado, nombre_producto, id_producto, cantidad, procesado, dispositivo) 
                           VALUES 
                             (:token, :codigo, 'Producto no registrado', NULL, :cant, 0, :disp)";
            $stmtCola = $this->conn->prepare($insertCola);
            $stmtCola->bindParam(':token', $token);
            $stmtCola->bindParam(':codigo', $criterio);
            $stmtCola->bindParam(':cant', $cantidad, PDO::PARAM_INT);
            $stmtCola->bindParam(':disp', $dispositivo);
            $stmtCola->execute();

            return [
                'ok'      => true,
                'no_registrado' => true,
                'codigo'  => $criterio,
                'mensaje' => "Código leído: '{$criterio}' (Listo para registrar en Inventario o Compra)."
            ];
        }

        $stockDisponible = (int)($producto['stock_disponible'] ?? 0);
        $idProd = (int)$producto['id_producto'];

        if ($stockDisponible <= 0) {
            // Guardar en cola para que Inventario o Compras puedan usarlo para reabastecer
            $insertCola = "INSERT INTO pos_escaner_cola 
                             (token_sesion, codigo_escaneado, nombre_producto, id_producto, cantidad, procesado, dispositivo) 
                           VALUES 
                             (:token, :codigo, :nombre, :id_prod, :cant, 0, :disp)";
            $stmtCola = $this->conn->prepare($insertCola);
            $stmtCola->bindParam(':token', $token);
            $stmtCola->bindParam(':codigo', $criterio);
            $stmtCola->bindParam(':nombre', $producto['nombre']);
            $stmtCola->bindParam(':id_prod', $idProd, PDO::PARAM_INT);
            $stmtCola->bindParam(':cant', $cantidad, PDO::PARAM_INT);
            $stmtCola->bindParam(':disp', $dispositivo);
            $stmtCola->execute();

            return [
                'ok'      => true,
                'codigo'  => $criterio,
                'nombre'  => $producto['nombre'],
                'mensaje' => "Código transmitido a la PC: {$criterio}"
            ];
        }

        // Agregar o incrementar en el carrito de sesion_caja
        $items = json_decode($sesion['items_json'] ?? '[]', true);
        if (!is_array($items)) {
            $items = [];
        }

        $idProd = (int)$producto['id_producto'];
        $encontrado = false;
        foreach ($items as &$item) {
            if ($item['id_producto'] == $idProd) {
                if ($item['cantidad'] + $cantidad > $stockDisponible) {
                    return [
                        'ok'      => false,
                        'mensaje' => "Stock insuficiente en almacén (Disponible: {$stockDisponible})."
                    ];
                }
                $item['cantidad'] += $cantidad;
                $item['subtotal'] = round($item['cantidad'] * $item['precio'], 2);
                $encontrado = true;
                break;
            }
        }
        unset($item);

        if (!$encontrado) {
            $precio = (float)$producto['precio'];
            $items[] = [
                'id_producto'   => $idProd,
                'codigo'        => $producto['codigo_barras'] ?: $producto['codigo'],
                'nombre'        => $producto['nombre'],
                'precio'        => $precio,
                'cantidad'      => $cantidad,
                'subtotal'      => round($precio * $cantidad, 2),
                'imagen_url'    => $producto['imagen_url'] ?? $producto['imagen'] ?? ''
            ];
        }

        // Recalcular total general
        $totalGeneral = 0;
        foreach ($items as $it) {
            $totalGeneral += $it['subtotal'];
        }
        $totalGeneral = round($totalGeneral, 2);

        // Actualizar sesión de caja
        $itemsJson = json_encode($items);
        $updateSes = "UPDATE sesion_caja 
                      SET items_json = :items, total_venta = :total, estado_conexion = 'conectado' 
                      WHERE token_emparejamiento = :token";
        $stmtUp = $this->conn->prepare($updateSes);
        $stmtUp->bindParam(':items', $itemsJson);
        $stmtUp->bindParam(':total', $totalGeneral);
        $stmtUp->bindParam(':token', $token);
        $stmtUp->execute();

        // Insertar en la cola de escaneo para notificación en tiempo real
        $insertCola = "INSERT INTO pos_escaner_cola 
                         (token_sesion, codigo_escaneado, nombre_producto, id_producto, cantidad, procesado, dispositivo) 
                       VALUES 
                         (:token, :codigo, :nombre, :id_prod, :cant, 0, :disp)";
        $stmtCola = $this->conn->prepare($insertCola);
        $stmtCola->bindParam(':token', $token);
        $stmtCola->bindParam(':codigo', $criterio);
        $stmtCola->bindParam(':nombre', $producto['nombre']);
        $stmtCola->bindParam(':id_prod', $idProd, PDO::PARAM_INT);
        $stmtCola->bindParam(':cant', $cantidad, PDO::PARAM_INT);
        $stmtCola->bindParam(':disp', $dispositivo);
        $stmtCola->execute();

        return [
            'ok'               => true,
            'mensaje'          => "¡Agregado exitosamente!",
            'producto'         => [
                'id_producto'   => $idProd,
                'nombre'        => $producto['nombre'],
                'precio'        => (float)$producto['precio'],
                'codigo_barras' => $producto['codigo_barras'],
                'stock'         => $stockDisponible
            ],
            'items'            => $items,
            'total_venta'      => $totalGeneral,
            'dispositivo'      => $dispositivo
        ];
    }

    /**
     * Consulta escaneos nuevos que la pantalla de la caja en la computadora aún no ha visto
     */
    public function obtenerEscaneosPendientes($token) {
        $query = "SELECT * FROM pos_escaner_cola 
                  WHERE (token_sesion = :token OR token_sesion = 'general_tienda_pos' OR :token2 = '' OR :token3 = 'general_tienda_pos') 
                    AND procesado = 0 
                  ORDER BY id_escaneo ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':token2', $token);
        $stmt->bindParam(':token3', $token);
        $stmt->execute();
        $escaneos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($escaneos)) {
            $ids = array_column($escaneos, 'id_escaneo');
            $inClause = implode(',', array_map('intval', $ids));
            $this->conn->exec("UPDATE pos_escaner_cola SET procesado = 1 WHERE id_escaneo IN ($inClause)");
        }

        return $escaneos;
    }
}
?>
