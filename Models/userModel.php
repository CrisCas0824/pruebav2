<?php
include_once 'Core/dataBase.php';

class UserModel{
    private $conn;

    public function __construct(){
        $dataBase = new DataBase();
        $this->conn = $dataBase->getConnection();
    }

    public function getUser(){
        $query = "SELECT * FROM usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createUser($nombre, $apellido, $correo, $password, $telefono, $rol, $fecha_registro): void{
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO usuario(nombre, apellido, correo, password, telefono, rol, fecha_registro) 
                    VALUES (:nombre, :apellido, :correo, :password, :telefono, :rol, :fecha_registro)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        $stmt->execute();
    }

    public function contarUsuarios(): int {
        $query = "SELECT COUNT(*) FROM usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function registrarUsuario($nombre, $correo, $password): void{
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');
        
        // Si no hay usuarios en el sistema, el primero se crea como 'admin' (máximo poder)
        $totalUsuarios = $this->contarUsuarios();
        $rol = ($totalUsuarios === 0) ? 'admin' : 'cliente';

        $query = "INSERT INTO usuario(nombre, correo, password, rol, fecha_registro, activo) 
                    VALUES (:nombre, :correo, :password, :rol, :fecha_registro, 1)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':fecha_registro', $fecha_registro);
        $stmt->execute();
    }

    public function getUserByEmail($correo){
        $query = "SELECT * FROM usuario WHERE correo = :correo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorGoogleId($googleId){
        $query = "SELECT * FROM usuario WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':google_id', $googleId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearUsuarioGoogle($correo, $googleId, $nombre, $apellido = '', $avatar = null){
        $total = $this->contarUsuarios();
        $rol = ($total === 0) ? 'admin' : 'cliente';
        $fecha = date('Y-m-d H:i:s');

        $query = "INSERT INTO usuario (nombre, apellido, correo, rol, auth_provider, google_id, avatar_url, fecha_registro, activo) 
                  VALUES (:nombre, :apellido, :correo, :rol, 'google', :google_id, :avatar, :fecha, 1)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':google_id', $googleId);
        $stmt->bindParam(':avatar', $avatar);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();

        return $this->getUserById($this->conn->lastInsertId());
    }

    public function getUserById($id){
        $query = "SELECT * FROM usuario WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarPassword($correo, $password): void{
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE usuario SET password = :password WHERE correo = :correo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
    }

    public function setLock($userId, $bloqueoHasta) {
        $stmt = $this->conn->prepare(
            "UPDATE usuario SET intentos_fallidos = 0, bloqueo_until = :bloqueo WHERE id_usuario = :id"
        );
        $stmt->bindParam(':bloqueo', $bloqueoHasta);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function setAttempts($userId, $attempts) {
        $stmt = $this->conn->prepare(
            "UPDATE usuario SET intentos_fallidos = :intentos WHERE id_usuario = :id"
        );
        $stmt->bindParam(':intentos', $attempts);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function resetFailedAttempts($userId) {
        $stmt = $this->conn->prepare(
            "UPDATE usuario SET intentos_fallidos = 0, bloqueo_until = NULL WHERE id_usuario = :id"
        );
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
}
?>