<?php
require_once 'Core/dataBase.php';

class CuentaModel{
    private $conn;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUsuarioById($idUsuario){
        $sql = "SELECT id_usuario, nombre, apellido, correo, telefono, rol, activo, fecha_registro
                FROM usuario WHERE id_usuario = :id_usuario LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPerfil($idUsuario, $nombre, $apellido, $correo, $telefono): bool {
        $sql = "UPDATE usuario SET nombre = :nombre, apellido = :apellido, correo = :correo, telefono = :telefono WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':correo', $correo);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function correoExisteExceptoUsuario($correo, $idUsuario): bool {
        $sql = "SELECT COUNT(*) FROM usuario WHERE correo = :correo AND id_usuario <> :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':correo', $correo);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public function getPasswordHash($idUsuario){
        $sql = "SELECT password FROM usuario WHERE id_usuario = :id_usuario LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function cambiarPassword($idUsuario, $nuevoHash): bool {
        $sql = "UPDATE usuario SET password = :password WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':password', $nuevoHash);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }
}