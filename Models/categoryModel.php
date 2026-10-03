<?php
include_once 'Core/dataBase.php';

class CategoryModel{
    private $conn;

    public function __construct(){
        $database = new DataBase();
        $this->conn = $database->getConnection();
    }

    public function getCategoria(){
        $query = "SELECT c.*, mc.nombre AS momento_nombre, gm.nombre AS grupo_nombre 
                  FROM categoria c
                  LEFT JOIN momento_consumo mc ON c.id_momento = mc.id_momento
                  LEFT JOIN grupo_momento gm ON c.id_grupo = gm.id_grupo
                  ORDER BY c.id_categoria ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCategory($nombre, $descripcion, $imagen, $id_momento = null, $id_grupo = null){
        $query = "INSERT INTO categoria (nombre, descripcion, imagen, id_momento, id_grupo) VALUES (:nombre, :descripcion, :imagen, :id_momento, :id_grupo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindValue(':id_momento', $id_momento ?: null, PDO::PARAM_INT);
        $stmt->bindValue(':id_grupo', $id_grupo ?: null, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateCategory($id, $nombre, $descripcion, $imagen, $id_momento = null, $id_grupo = null){
        if ($imagen !== null) {
            $query = "UPDATE categoria SET nombre = :nombre, descripcion = :descripcion, imagen = :imagen, id_momento = :id_momento, id_grupo = :id_grupo WHERE id_categoria = :id";
        } else {
            $query = "UPDATE categoria SET nombre = :nombre, descripcion = :descripcion, id_momento = :id_momento, id_grupo = :id_grupo WHERE id_categoria = :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindValue(':id_momento', $id_momento ?: null, PDO::PARAM_INT);
        $stmt->bindValue(':id_grupo', $id_grupo ?: null, PDO::PARAM_INT);
        if ($imagen !== null) {
            $stmt->bindParam(':imagen', $imagen);
        }
        return $stmt->execute();
    }

    public function getCategoryById($id){
        $query = "SELECT * FROM categoria WHERE id_categoria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteCategory($id) {
        $query = "UPDATE categoria SET activo = 0 WHERE id_categoria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function toggleStatus($id, $estado = null) {
        if ($estado !== null) {
            $query = "UPDATE categoria SET activo = :estado WHERE id_categoria = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        } else {
            $query = "UPDATE categoria SET activo = CASE WHEN COALESCE(activo,1) = 1 THEN 0 ELSE 1 END WHERE id_categoria = :id";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>