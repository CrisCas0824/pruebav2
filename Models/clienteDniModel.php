<?php
require_once __DIR__ . '/../Core/dataBase.php';

/**
 * Modelo para Consulta de DNI y Clientes en Caja POS y Ventas
 * Soporta API externa de RENIEC / DNI Perú con timeout seguro,
 * caché en base de datos local y clientes genéricos (00000000 / 99999999).
 */
class ClienteDniModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    /**
     * DNI Genéricos aceptados para ventas rápidas en mostrador
     */
    public function obtenerClienteGenerico($tipo = '00000000') {
        return [
            'ok'              => true,
            'dni'             => $tipo === '99999999' ? '99999999' : '00000000',
            'nombre_completo' => 'Público General',
            'nombres'         => 'Público',
            'apellido_paterno'=> 'General',
            'apellido_materno'=> '',
            'tipo_cliente'    => 'generico',
            'origen'          => 'sistema'
        ];
    }

    /**
     * Consulta un DNI:
     * 1. Si es genérico (00000000 / 99999999 / vacío), retorna Público General.
     * 2. Si ya existe en la base de datos local, lo devuelve de inmediato (súper rápido).
     * 3. Si no existe, consulta API REST externa de DNI con timeout de 3 segundos.
     */
    public function consultarDni($dni) {
        return $this->consultarDocumento($dni);
    }

    public function consultarDocumento($documento) {
        $documento = trim((string)$documento);
        if (in_array($documento, ['', '00000000', '99999999', '0'], true)) {
            return $this->obtenerClienteGenerico($documento);
        }

        $tipo = strlen($documento) === 8 ? 'dni' : (strlen($documento) === 11 ? 'ruc' : '');
        if ($tipo === '' || !ctype_digit($documento)) {
            return ['ok' => false, 'mensaje' => 'Ingresa un DNI de 8 dígitos o un RUC de 11 dígitos.'];
        }

        $clienteLocal = $this->buscarEnBaseDatosLocal($documento);
        if ($clienteLocal) {
            return [
                'ok' => true,
                'dni' => $documento,
                'nombre_completo' => $clienteLocal['nombre_completo'],
                'razon_social' => $tipo === 'ruc' ? $clienteLocal['nombre_completo'] : '',
                'nombres' => $clienteLocal['nombres'],
                'apellido_paterno' => $clienteLocal['apellido_paterno'] ?? '',
                'apellido_materno' => $clienteLocal['apellido_materno'] ?? '',
                'telefono' => $clienteLocal['telefono'] ?? '',
                'tipo_cliente' => 'registrado',
                'origen' => 'base_datos_local'
            ];
        }

        $resultadoApi = $this->consultarApiDocumento($documento, $tipo);
        if ($resultadoApi['ok']) {
            return $resultadoApi;
        }

        return [
            'ok' => false,
            'dni' => $documento,
            'mensaje' => $resultadoApi['mensaje']
        ];
    }

    /**
     * Busca el DNI en la base de datos de usuarios o pedidos previos
     */
    private function buscarEnBaseDatosLocal($dni) {
        // En tabla usuario
        $query = "SELECT id_usuario, nombre, apellido, telefono, dni 
                  FROM usuario 
                  WHERE dni = :dni LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dni', $dni);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $nombreComp = trim($user['nombre'] . ' ' . ($user['apellido'] ?? ''));
            return [
                'nombre_completo' => $nombreComp,
                'nombres'         => $user['nombre'],
                'apellido_paterno'=> $user['apellido'] ?? '',
                'telefono'        => $user['telefono'] ?? '',
                'direccion'       => ''
            ];
        }

        // En histórico de pedidos
        $queryPed = "SELECT cliente_nombre 
                     FROM pedido 
                     WHERE cliente_dni = :dni 
                       AND cliente_nombre IS NOT NULL 
                       AND cliente_nombre != 'Público General' 
                     ORDER BY id_pedido DESC LIMIT 1";
        $stmtPed = $this->conn->prepare($queryPed);
        $stmtPed->bindParam(':dni', $dni);
        $stmtPed->execute();
        $ped = $stmtPed->fetch(PDO::FETCH_ASSOC);

        if ($ped && !empty($ped['cliente_nombre'])) {
            return [
                'nombre_completo' => $ped['cliente_nombre'],
                'nombres'         => $ped['cliente_nombre'],
                'apellido_paterno'=> '',
                'telefono'        => '',
                'direccion'       => ''
            ];
        }

        return null;
    }

    private function consultarApiDocumento($documento, $tipo) {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'mensaje' => 'La consulta externa requiere habilitar cURL en PHP. Puedes ingresar el nombre manualmente.'];
        }

        $token = getenv('APIS_NET_PE_TOKEN') ?: '';
        $ruta = $tipo === 'dni' ? 'reniec/dni' : 'sunat/ruc';
        $consultas = [];
        if ($token !== '') {
            $consultas[] = [
                'url' => 'https://api.apis.net.pe/v2/' . $ruta . '?numero=' . urlencode($documento),
                'token' => $token
            ];
        }
        $consultas[] = [
            'url' => 'https://api.decolector.com/api/v1/' . ($tipo === 'dni' ? 'reniec/dni' : 'sunat/ruc') . '?numero=' . urlencode($documento),
            'token' => ''
        ];

        foreach ($consultas as $consulta) {
            $headers = ['Accept: application/json', 'User-Agent: LaCaseritaPOS/1.0'];
            if ($consulta['token'] !== '') $headers[] = 'Authorization: Bearer ' . $consulta['token'];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $consulta['url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers
            ]);
            $response = curl_exec($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if (!$response || $httpCode < 200 || $httpCode >= 300) continue;
            $data = json_decode($response, true);
            if (!is_array($data)) continue;
            if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];
            if (isset($data['resultado']) && is_array($data['resultado'])) $data = $data['resultado'];

            $nombres = trim($data['nombres'] ?? '');
            $paterno = trim($data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? '');
            $materno = trim($data['apellidoMaterno'] ?? $data['apellido_materno'] ?? '');
            $razonSocial = trim($data['razonSocial'] ?? $data['razon_social'] ?? $data['nombre_o_razon_social'] ?? '');
            $nombre = trim($data['nombreCompleto'] ?? $data['nombre_completo'] ?? $data['full_name'] ?? '');
            if ($tipo === 'ruc') $nombre = $razonSocial ?: $nombre ?: trim($data['nombre'] ?? '');
            if ($nombre === '' && $tipo === 'dni') $nombre = trim("{$nombres} {$paterno} {$materno}");

            if ($nombre !== '') {
                return [
                    'ok' => true,
                    'dni' => $documento,
                    'nombre_completo' => $nombre,
                    'razon_social' => $tipo === 'ruc' ? $nombre : '',
                    'nombres' => $nombres,
                    'apellido_paterno' => $paterno,
                    'apellido_materno' => $materno,
                    'tipo_cliente' => $tipo === 'ruc' ? 'empresa' : 'reniec',
                    'origen' => $tipo === 'ruc' ? 'sunat' : 'reniec'
                ];
            }
        }

        $nombreServicio = $tipo === 'ruc' ? 'SUNAT' : 'RENIEC';
        $mensaje = $token === ''
            ? "No se obtuvo respuesta de {$nombreServicio}. Configura APIS_NET_PE_TOKEN en el entorno de Apache para habilitar la consulta o ingresa el nombre manualmente."
            : "No se obtuvo respuesta de {$nombreServicio}. Verifica el token APIS_NET_PE_TOKEN, la conexión a internet y el número; también puedes ingresar el nombre manualmente.";
        return ['ok' => false, 'mensaje' => $mensaje];
    }
}
?>
