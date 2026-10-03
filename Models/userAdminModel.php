<?php
include_once 'Core/dataBase.php';

class UserAdminModel {
    private $conn;

    public function __construct() {
        $dataBase = new DataBase();
        $this->conn = $dataBase->getConnection();
    }

    public function getUsuarios() {
        $query = "SELECT u.*,
                    (
                        (SELECT COUNT(*) FROM pedido p WHERE p.id_usuario = u.id_usuario OR p.id_cajero = u.id_usuario) +
                        (SELECT COUNT(*) FROM reposicion r WHERE r.id_usuario = u.id_usuario) +
                        (SELECT COUNT(*) FROM envio_delivery ed WHERE ed.id_repartidor = u.id_usuario) +
                        (SELECT COUNT(*) FROM sesion_caja sc WHERE sc.id_usuario = u.id_usuario) +
                        (SELECT COUNT(*) FROM movimiento_caja mc WHERE mc.id_usuario = u.id_usuario)
                    ) AS total_actividad
                  FROM usuario u 
                  ORDER BY u.fecha_registro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function($row) {
            return array_change_key_case($row, CASE_LOWER);
        }, $results);
    }

    public function getUsuarioById($id_usuario) {
        $query = "SELECT * FROM usuario WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? array_change_key_case($result, CASE_LOWER) : false;
    }

    public function createAdministrador($nombre, $apellido, $correo, $telefono, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');
        $query = "INSERT INTO usuario (nombre, apellido, correo, telefono, password, rol, activo, fecha_registro) 
                  VALUES (:nombre, :apellido, :correo, :telefono, :password, 'admin', 1, :fecha_registro)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        return $stmt->execute();
    }

    public function createCajero($nombre, $apellido, $correo, $telefono, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');
        $query = "INSERT INTO usuario (nombre, apellido, correo, telefono, password, rol, activo, fecha_registro) 
                  VALUES (:nombre, :apellido, :correo, :telefono, :password, 'cajero', 1, :fecha_registro)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        return $stmt->execute();
    }

    public function createRepartidor($nombre, $apellido, $correo, $telefono, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');
        $query = "INSERT INTO usuario (nombre, apellido, correo, telefono, password, rol, activo, fecha_registro) 
                  VALUES (:nombre, :apellido, :correo, :telefono, :password, 'repartidor', 1, :fecha_registro)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        return $stmt->execute();
    }

    public function updateUsuario($id_usuario, $nombre, $apellido, $correo, $telefono, $rol, $password = null) {
        if ($password) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE usuario SET nombre = :nombre, apellido = :apellido, correo = :correo, 
                      telefono = :telefono, rol = :rol, password = :password WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $passwordHash);
        } else {
            $query = "UPDATE usuario SET nombre = :nombre, apellido = :apellido, correo = :correo, 
                      telefono = :telefono, rol = :rol WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cambiarEstadoUsuario($id_usuario, $activo) {
        $query = "UPDATE usuario SET activo = :activo WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function contarAdministradoresActivos() {
        $query = "SELECT COUNT(*) as total FROM usuario WHERE rol = 'admin' AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function correoExiste($correo) {
        $query = "SELECT id_usuario FROM usuario WHERE correo = :correo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function correoExisteExceptoUsuario($correo, $id_usuario) {
        $query = "SELECT id_usuario FROM usuario WHERE correo = :correo AND id_usuario != :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function contarActividadUsuario($id_usuario) {
        $sql = "SELECT (
                    (SELECT COUNT(*) FROM pedido WHERE id_usuario = :id1 OR id_cajero = :id2) +
                    (SELECT COUNT(*) FROM reposicion WHERE id_usuario = :id3) +
                    (SELECT COUNT(*) FROM envio_delivery WHERE id_repartidor = :id4) +
                    (SELECT COUNT(*) FROM sesion_caja WHERE id_usuario = :id5) +
                    (SELECT COUNT(*) FROM movimiento_caja WHERE id_usuario = :id6)
                ) AS total";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id1', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id2', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id3', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id4', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id5', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':id6', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function eliminarUsuario($id_usuario) {
        $u = $this->getUsuarioById($id_usuario);
        if (!$u) return false;
        if ($u['rol'] === 'admin' && $this->contarAdministradoresActivos() <= 1) {
            return false;
        }
        if ($this->contarActividadUsuario($id_usuario) > 0) {
            return false;
        }
        $query = "DELETE FROM usuario WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
