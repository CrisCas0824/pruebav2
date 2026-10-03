<?php
include_once 'Models/modelo_punto_venta.php';
require_once 'Models/cajaModel.php';

class ControladorPuntoVenta {
    private $model;
    private $cajaModel;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ModeloPuntoVenta();
        $this->cajaModel = new CajaModel();
    }

    private function verificarRolCajero() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?action=login');
            exit();
        }

        $rol = $_SESSION['usuario_rol'] ?? '';
        if ($rol !== 'cajero' && $rol !== 'admin') {
            $_SESSION['auth_error'] = 'Acceso no autorizado. Debe iniciar sesión como Cajero.';
            header('Location: index.php?action=login');
            exit();
        }
    }

    private function tokenPerteneceCajaActivaDelUsuario($token) {
        if (!isset($_SESSION['usuario_id']) || trim((string)$token) === '') return false;
        $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
        return $sesion
            && ($sesion['estado'] ?? '') === 'abierta'
            && !empty($sesion['token_emparejamiento'])
            && hash_equals((string)$sesion['token_emparejamiento'], (string)$token);
    }

    private function obtenerTokenSesionAbiertaActual() {
        require_once 'Core/dataBase.php';
        $db = (new DataBase())->getConnection();
        $stmt = $db->query("SELECT token_emparejamiento FROM sesion_caja WHERE estado = 'abierta' ORDER BY id_sesion DESC LIMIT 1");
        $sesion = $stmt->fetch(PDO::FETCH_ASSOC);
        return $sesion['token_emparejamiento'] ?? '';
    }

    private function rutaTokenEscanerActivo() {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . 'caserita_scanner_' . hash('sha256', __DIR__) . '.token';
    }

    private function guardarEscanerActivo($token) {
        if ($token === '' || $token === 'general_tienda_pos') return;
        $stampDir = __DIR__ . '/../scratch';
        if (!is_dir($stampDir)) @mkdir($stampDir, 0777, true);
        @file_put_contents($stampDir . '/celular_' . hash('sha256', $token) . '.timestamp', time());
        @file_put_contents($stampDir . '/celular_activo.timestamp', time());
        @file_put_contents($this->rutaTokenEscanerActivo(), $token);
    }

    private function obtenerTokenEscanerActivo() {
        $stampDir = __DIR__ . '/../scratch';
        $stampFile = $stampDir . '/celular_activo.timestamp';
        $tokenFile = $this->rutaTokenEscanerActivo();
        if (!is_file($stampFile) || !is_file($tokenFile)) return '';
        $lastPing = (int)@file_get_contents($stampFile);
        if (time() - $lastPing > 12) return '';
        return trim((string)@file_get_contents($tokenFile));
    }

    public function pos() {
        $this->verificarRolCajero();

        $idUsuario = (int)$_SESSION['usuario_id'];
        $sesionActiva = $this->cajaModel->obtenerSesionActiva($idUsuario);
        if (!$sesionActiva) {
            $_SESSION['flash_error'] = 'Para ingresar a Caja POS y realizar ventas, primero debes aperturar la caja indicando el fondo inicial.';
            $destino = ($_SESSION['usuario_rol'] ?? '') === 'admin' ? 'caja_admin' : 'panel_cajero';
            header('Location: index.php?action=' . $destino);
            exit();
        }

        $sesionCaja = $this->model->obtenerSesionPorToken($sesionActiva['token_emparejamiento']);
        $stampFile = __DIR__ . '/../scratch/celular_' . hash('sha256', $sesionCaja['token_emparejamiento']) . '.timestamp';
        $lastMobilePing = is_file($stampFile) ? (int)@file_get_contents($stampFile) : 0;
        $sharedMobilePing = is_file(__DIR__ . '/../scratch/celular_activo.timestamp')
            ? (int)@file_get_contents(__DIR__ . '/../scratch/celular_activo.timestamp')
            : 0;
        $lastMobilePing = max($lastMobilePing, $sharedMobilePing);
        $sesionCaja['estado_conexion'] = time() - $lastMobilePing <= 12 ? 'conectado' : 'esperando';

        include 'Views/Cajero/caja_punto_venta.php';
    }

    private function validarCsrfCaja() {
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        $tokenRecibido = $_POST['csrf_token'] ?? '';
        if ($tokenSesion === '' || $tokenRecibido === '' || !hash_equals($tokenSesion, (string)$tokenRecibido)) {
            $_SESSION['flash_error'] = 'La sesión de seguridad expiró. Recarga la página.';
            header('Location: index.php?action=arqueo_caja');
            exit();
        }
    }

    public function panelCaja() {
        $this->verificarRolCajero();
        $idUsuario = (int)$_SESSION['usuario_id'];
        $sesionActiva = $this->cajaModel->obtenerSesionActiva($idUsuario);
        $resumen = $sesionActiva ? $this->cajaModel->calcularResumenCaja((int)$sesionActiva['id_sesion']) : null;
        
        $historialVentas = $this->model->obtenerHistorialVentasCajero($idUsuario, 15);
        $totalVentas = $resumen ? (float)$resumen['total_ventas'] : 0.0;
        $totalTickets = $resumen ? (int)$resumen['total_tickets'] : 0;
        $ticketPromedio = $totalTickets > 0 ? ($totalVentas / $totalTickets) : 0.0;

        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Cajero');
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        include 'Views/Cajero/dashboard_caja.php';
    }

    public function productosCatalogo() {
        $this->verificarRolCajero();
        $productos = $this->model->listarProductosCatalogo();
        include 'Views/Cajero/productos.php';
    }

    public function arqueoCaja() {
        $this->verificarRolCajero();
        $idUsuario = (int)$_SESSION['usuario_id'];
        $sesionActiva = $this->cajaModel->obtenerSesionActiva($idUsuario);
        $resumen = $sesionActiva ? $this->cajaModel->calcularResumenCaja((int)$sesionActiva['id_sesion']) : null;
        $movimientos = $sesionActiva ? $this->cajaModel->listarMovimientos((int)$sesionActiva['id_sesion']) : [];
        $historialSesiones = $this->cajaModel->listarHistorialSesionesUsuario($idUsuario, 20);
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Cajero');
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        include 'Views/Cajero/panel_caja.php';
    }

    public function abrirCajaCajero() {
        $this->verificarRolCajero();
        $this->validarCsrfCaja();
        $monto = filter_input(INPUT_POST, 'monto_apertura', FILTER_VALIDATE_FLOAT);
        if ($monto === false || $monto === null || $monto < 0) {
            $_SESSION['flash_error'] = 'Ingresa un fondo inicial válido.';
        } else {
            $idUsuario = (int)$_SESSION['usuario_id'];
            $resultado = $this->cajaModel->abrirCaja($idUsuario, (float)$monto, 'CAJA-CJ-' . $idUsuario);
            $_SESSION[$resultado['ok'] ? 'flash_success' : 'flash_error'] = $resultado['mensaje'];
        }
        header('Location: index.php?action=arqueo_caja');
        exit();
    }

    public function registrarMovimientoCajaCajero() {
        $this->verificarRolCajero();
        $this->validarCsrfCaja();
        $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
        $tipo = strtoupper(trim($_POST['tipo'] ?? ''));
        $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
        $motivo = trim($_POST['motivo'] ?? '');
        if (!$sesion || !in_array($tipo, ['INGRESO', 'EGRESO'], true) || $monto === false || $monto <= 0 || $motivo === '') {
            $_SESSION['flash_error'] = 'Completa el tipo, monto y motivo del movimiento.';
        } else {
            $categoria = trim($_POST['categoria'] ?? 'otro');
            $comprobante = trim($_POST['comprobante'] ?? '');
            $resultado = $this->cajaModel->registrarMovimiento((int)$sesion['id_sesion'], (int)$_SESSION['usuario_id'], $tipo, $categoria, (float)$monto, $motivo, $comprobante);
            $_SESSION[$resultado['ok'] ? 'flash_success' : 'flash_error'] = $resultado['mensaje'];
        }
        header('Location: index.php?action=arqueo_caja');
        exit();
    }

    public function cerrarCajaCajero() {
        $this->verificarRolCajero();
        $this->validarCsrfCaja();
        $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
        $montoReal = filter_input(INPUT_POST, 'monto_cierre_real', FILTER_VALIDATE_FLOAT);
        if (!$sesion || $montoReal === false || $montoReal === null || $montoReal < 0) {
            $_SESSION['flash_error'] = 'Ingresa el efectivo contado para cerrar tu caja.';
        } else {
            $resultado = $this->cajaModel->cerrarCaja((int)$sesion['id_sesion'], (float)$montoReal);
            $_SESSION[$resultado['ok'] ? 'flash_success' : 'flash_error'] = $resultado['mensaje'];
        }
        header('Location: index.php?action=arqueo_caja');
        exit();
    }

    public function historial() {
        $this->verificarRolCajero();
        $ventas = $this->model->obtenerHistorialVentasCajero((int)$_SESSION['usuario_id']);
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Cajero');
        include 'Views/Cajero/historial.php';
    }

    public function apiGenerarQR() {
        $this->verificarRolCajero();
        header('Content-Type: application/json');

        $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
        if (!$sesion) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Debes abrir la caja desde Arqueo de caja antes de conectar el escáner.']);
            exit();
        }

        $payload = [
            'status' => 'success',
            'caja_id' => $sesion['caja_id'],
            'token' => $sesion['token_emparejamiento'],
            'estado_conexion' => $sesion['estado_conexion'],
            'qr_data' => json_encode([
                'caja_id' => $sesion['caja_id'],
                'token'   => $sesion['token_emparejamiento'],
                'app'     => 'LaCaseritaPOS'
            ])
        ];

        echo json_encode($payload);
        exit();
    }

    public function apiValidarEmparejamiento() {
        header('Content-Type: application/json');
        
        $token = trim($_POST['token'] ?? ($_GET['token'] ?? ''));
        $dispositivo = trim($_POST['dispositivo_info'] ?? ($_GET['dispositivo_info'] ?? 'Celular Móvil (Cable USB)'));

        if (stripos($dispositivo, 'celular') !== false || stripos($dispositivo, 'lector') !== false) {
            $tokenActual = $this->obtenerTokenSesionAbiertaActual();
            if ($tokenActual !== '') $token = $tokenActual;
        }

        if (empty($token)) {
            require_once 'Core/dataBase.php';
            $db = (new DataBase())->getConnection();
            $stmt = $db->query("SELECT token_emparejamiento FROM sesion_caja WHERE estado = 'abierta' ORDER BY id_sesion DESC LIMIT 1");
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r && !empty($r['token_emparejamiento'])) {
                $token = $r['token_emparejamiento'];
            }
        }

        if (empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Token de emparejamiento faltante']);
            exit();
        }

        $exito = $this->model->validarEmparejamiento($token, $dispositivo);
        if ($exito) {
            $this->guardarEscanerActivo($token);
            echo json_encode(['status' => 'success', 'token' => $token, 'message' => 'Escáner emparejado correctamente con la caja']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Token de caja inválido o expirado']);
        }
        exit();
    }

    public function apiEstadoSesion() {
        header('Content-Type: application/json');

        $token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
        if (empty($token) && isset($_SESSION['usuario_id'])) {
            $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
            if (!$sesion) {
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'Debes abrir la caja desde Arqueo de caja antes de vender.']);
                exit();
            }
            $token = $sesion['token_emparejamiento'] ?? '';
        }

        if (empty($token)) {
            require_once 'Core/dataBase.php';
            $db = (new DataBase())->getConnection();
            $stmt = $db->query("SELECT token_emparejamiento FROM sesion_caja WHERE estado = 'abierta' ORDER BY id_sesion DESC LIMIT 1");
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r && !empty($r['token_emparejamiento'])) {
                $token = $r['token_emparejamiento'];
            }
        }

        if (empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Sin token de sesión']);
            exit();
        }

        $sesionData = $this->model->obtenerSesionPorToken($token);
        if (!$sesionData) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no encontrada']);
            exit();
        }

        $items = json_decode($sesionData['items_json'] ?? '[]', true);

        $stampFile = __DIR__ . '/../scratch/celular_' . hash('sha256', $token) . '.timestamp';
        $lastMobilePing = is_file($stampFile) ? (int)@file_get_contents($stampFile) : 0;
        $sharedMobilePing = is_file(__DIR__ . '/../scratch/celular_activo.timestamp')
            ? (int)@file_get_contents(__DIR__ . '/../scratch/celular_activo.timestamp')
            : 0;
        $lastMobilePing = max($lastMobilePing, $sharedMobilePing);
        $connectionState = time() - $lastMobilePing <= 12 ? $sesionData['estado_conexion'] : 'esperando';

        echo json_encode([
            'status' => 'success',
            'token' => $sesionData['token_emparejamiento'],
            'estado_conexion' => $connectionState,
            'dispositivo_info' => $sesionData['dispositivo_info'],
            'total_venta' => (float)$sesionData['total_venta'],
            'items' => $items
        ]);
        exit();
    }

    public function apiBuscarProductos() {
        header('Content-Type: application/json');

        $query = trim($_GET['q'] ?? ($_GET['term'] ?? ($_POST['q'] ?? '')));
        if ($query === '') {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit();
        }

        try {
            $productos = $this->model->buscarProductosManual($query);
            echo json_encode([
                'status' => 'success',
                'data' => $productos ?: []
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al buscar productos: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function apiEscanearProducto() {
        header('Content-Type: application/json');

        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
        $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $codigoBarras = filter_input(INPUT_POST, 'codigo_barras', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$token || (!$codigoBarras && !$idProducto)) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos (token y código son requeridos)']);
            exit();
        }

        if (!$this->tokenPerteneceCajaActivaDelUsuario($token)) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Abre tu caja desde Arqueo de caja antes de agregar productos.']);
            exit();
        }

        $resultado = $this->model->agregarProductoPorCodigo($token, $idProducto ?: $codigoBarras, (bool)$idProducto);
        echo json_encode($resultado);
        exit();
    }

    public function apiActualizarCantidad() {
        header('Content-Type: application/json');

        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
        $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

        if (!$token || !$idProducto || $cantidad === null) {
            echo json_encode(['status' => 'error', 'message' => 'Datos de ítem inválidos']);
            exit();
        }

        if (!$this->tokenPerteneceCajaActivaDelUsuario($token)) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Abre tu caja desde Arqueo de caja antes de modificar productos.']);
            exit();
        }

        $res = $this->model->actualizarCantidadItem($token, $idProducto, $cantidad);
        if ($res === false) {
            echo json_encode(['status' => 'error', 'message' => 'La caja está cerrada o ya no existe.']);
            exit();
        }
        echo json_encode(['status' => 'success', 'data' => $res]);
        exit();
    }

    public function apiProcesarVenta() {
        header('Content-Type: application/json');

        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
        $metodoPago = filter_input(INPUT_POST, 'metodo_pago', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'efectivo';
        $montoRecibido = filter_input(INPUT_POST, 'monto_recibido', FILTER_VALIDATE_FLOAT) ?? 0;
        $clienteDni = filter_input(INPUT_POST, 'cliente_dni', FILTER_SANITIZE_SPECIAL_CHARS) ?? '00000000';
        $clienteNombre = filter_input(INPUT_POST, 'cliente_nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Público General';

        if (!$token) {
            echo json_encode(['status' => 'error', 'message' => 'Token de sesión requerido']);
            exit();
        }

        if (!$this->tokenPerteneceCajaActivaDelUsuario($token)) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Debes abrir tu caja desde Arqueo de caja antes de procesar una venta.']);
            exit();
        }

        $resultado = $this->model->finalizarVenta($token, $metodoPago, $montoRecibido, $clienteDni, $clienteNombre);
        echo json_encode($resultado);
        exit();
    }

    public function apiConsultarDni() {
        header('Content-Type: application/json');

         $documento = trim($_GET['documento'] ?? $_GET['dni'] ?? $_POST['documento'] ?? $_POST['dni'] ?? '');

        require_once 'Models/clienteDniModel.php';
        $dniModel = new ClienteDniModel();
         $resultado = $dniModel->consultarDocumento($documento);
        echo json_encode($resultado);
        exit();
    }

    public function vistaEscanerMovil() {
        $token = trim($_GET['token'] ?? ($_GET['amp;token'] ?? ''));
        if (empty($token)) {
            require_once 'Core/dataBase.php';
            $db = (new DataBase())->getConnection();
            $stmt = $db->query("SELECT token_emparejamiento FROM sesion_caja WHERE estado = 'abierta' ORDER BY id_sesion DESC LIMIT 1");
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r && !empty($r['token_emparejamiento'])) {
                $token = $r['token_emparejamiento'];
            }
        }
        include 'Views/Cajero/escaner_movil.php';
        exit();
    }

    public function apiEscanearMovil() {
        header('Content-Type: application/json');

        $token = trim($_POST['token'] ?? ($_POST['amp;token'] ?? ($_GET['token'] ?? ($_GET['amp;token'] ?? ''))));
        $codigo = trim($_POST['codigo'] ?? ($_POST['codigo_barras'] ?? ($_GET['codigo'] ?? ($_GET['codigo_barras'] ?? ''))));
        $dispositivo = trim($_POST['dispositivo'] ?? 'Celular Lector (Cable USB)');

        $sesionToken = $token !== '' ? $this->model->obtenerSesionPorToken($token) : null;
        if (!$sesionToken || ($sesionToken['estado'] ?? '') !== 'abierta') {
            $token = $this->obtenerTokenSesionAbiertaActual();
            $sesionToken = $token !== '' ? $this->model->obtenerSesionPorToken($token) : null;
        }
        if (!$sesionToken || ($sesionToken['estado'] ?? '') !== 'abierta') {
            http_response_code(409);
            echo json_encode(['ok' => false, 'mensaje' => 'No hay una caja abierta para recibir escaneos.']);
            exit();
        }

        if (empty($codigo)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Parámetro código requerido']);
            exit();
        }

        require_once 'Models/posScannerModel.php';
        $scannerModel = new PosScannerModel();
        $resultado = $scannerModel->registrarEscaneoRemoto($token, $codigo, $dispositivo);
        if (!empty($resultado['ok'])) $this->guardarEscanerActivo($token);
        echo json_encode($resultado);
        exit();
    }

    public function apiObtenerEscaneosPendientes() {
        header('Content-Type: application/json');

        $token = trim($_GET['token'] ?? ($_GET['amp;token'] ?? ''));
        if (empty($token)) {
            $token = $this->obtenerTokenEscanerActivo();
        }
        if (empty($token)) {
            $token = $this->obtenerTokenSesionAbiertaActual();
        }
        $sesion = $token !== '' ? $this->model->obtenerSesionPorToken($token) : null;
        if (!$sesion || ($sesion['estado'] ?? '') !== 'abierta') {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit();
        }

        require_once 'Models/posScannerModel.php';
        $scannerModel = new PosScannerModel();
        $pendientes = $scannerModel->obtenerEscaneosPendientes($token);
        echo json_encode(['status' => 'success', 'data' => $pendientes]);
        exit();
    }

    public function apiAbrirLectorEnCelular() {
        $this->verificarRolCajero();
        header('Content-Type: application/json');

        $sesion = $this->cajaModel->obtenerSesionActiva((int)$_SESSION['usuario_id']);
        $tokenEnviado = trim($_POST['token'] ?? '');
        if (!$sesion || ($tokenEnviado !== '' && !hash_equals((string)$sesion['token_emparejamiento'], $tokenEnviado))) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'mensaje' => 'Abre tu caja desde Arqueo de caja antes de conectar el escáner.']);
            exit();
        }
        $token = (string)$sesion['token_emparejamiento'];

        $stampDir = __DIR__ . '/../scratch';
        $stampFile = $stampDir . '/celular_' . hash('sha256', $token) . '.timestamp';
        $lastPing = file_exists($stampFile) ? (int)@file_get_contents($stampFile) : 0;
        $sharedPing = file_exists($stampDir . '/celular_activo.timestamp') ? (int)@file_get_contents($stampDir . '/celular_activo.timestamp') : 0;
        $lastPing = max($lastPing, $sharedPing);
        $yaConectado = (time() - $lastPing) < 15;

        // Si ya está conectado y activo en los últimos 15 segundos, NO abrir otra pestaña
        if ($yaConectado) {
            echo json_encode([
                'ok' => true,
                'ya_conectado' => true,
                'mensaje' => 'El celular ya está conectado y listo para escanear'
            ]);
            exit();
        }

        $adbCandidates = [
            'C:\\Users\\smt2d\\AppData\\Local\\Android\\Sdk\\platform-tools\\adb.exe',
            (getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Android\\Sdk\\platform-tools\\adb.exe' : ''),
            'C:\\platform-tools\\adb.exe'
        ];
        $adb = '';
        foreach ($adbCandidates as $cand) {
            if (!empty($cand) && file_exists($cand)) {
                $adb = $cand;
                break;
            }
        }

        if (empty($adb)) {
            echo json_encode(['ok' => false, 'mensaje' => 'ADB no encontrado en la PC']);
            exit();
        }

        exec("\"$adb\" reverse tcp:8080 tcp:80", $reverseOutput, $reverseExitCode);
        if ($reverseExitCode !== 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'ADB no pudo conectar el teléfono. Desbloquéalo y acepta la depuración USB.']);
            exit();
        }

        // 2. Obtener token de caja
        // 3. Abrir en Chrome del celular reutilizando la pestaña actual si existe
        $url = "http://localhost:8080/CALIDAD%20SOFTWARE/Web_mejorar/Proyecto_Teconoligas%20Web/index.php?action=pos_escaner_movil";
        if ($token) {
            $url .= "&token=" . $token;
        }
        $cmd = "\"$adb\" shell \"am start -a android.intent.action.VIEW -d '$url' -n com.android.chrome/com.google.android.apps.chrome.Main --es com.android.browser.application_id com.android.chrome\"";
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'No se pudo abrir el escáner en el teléfono. Verifica que Chrome esté instalado y vuelve a intentar.']);
            exit();
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Escáner conectado exitosamente en la pantalla de tu celular', 'url' => $url]);
        exit();
    }
}
?>
