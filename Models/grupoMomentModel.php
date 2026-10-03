<?php
include_once 'Core/dataBase.php';

class GrupMomentModel{
    private $conn;

    public function __construct(){
        $dataBase = new DataBase();
        $this->conn = $dataBase->getConnection();
    }

    public function getGrupo(){
        $query = "SELECT gm.*, mc.nombre AS momento_nombre,
                         GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias_nombres,
                         GROUP_CONCAT(c.id_categoria) AS categorias_ids,
                         COUNT(c.id_categoria) AS total_categorias
                  FROM grupo_momento gm
                  INNER JOIN momento_consumo mc ON gm.id_momento = mc.id_momento
                  LEFT JOIN categoria c ON c.id_grupo = gm.id_grupo
                  GROUP BY gm.id_grupo, gm.id_momento, gm.nombre, gm.descripcion, gm.imagen, gm.color, gm.activo, mc.nombre
                  ORDER BY mc.id_momento, gm.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createGrupMoment($id_momento, $nombre, $descripcion, $imagen = null, $color = null, $categorias_ids = []){
        $color = $color ?: '#15803d';
        $query = "INSERT INTO grupo_momento
                    (id_momento, nombre, descripcion, imagen, color, activo)
                    VALUES (:id_momento, :nombre, :descripcion, :imagen, :color, 1)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_momento', $id_momento, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':color', $color);

        if ($stmt->execute()) {
            $newId = (int)$this->conn->lastInsertId();
            if (!empty($categorias_ids)) {
                $this->asignarCategorias($newId, $id_momento, $categorias_ids);
            }
            return $newId;
        }
        return false;
    }

    public function updateGrupMoment($id, $id_momento, $nombre, $descripcion, $imagen = null, $color = null, $categorias_ids = []) {
        $color = $color ?: '#15803d';
        if ($imagen !== null) {
            $query = "UPDATE grupo_momento SET id_momento = :id_momento, nombre = :nombre, descripcion = :descripcion, color = :color, imagen = :imagen WHERE id_grupo = :id";
        } else {
            $query = "UPDATE grupo_momento SET id_momento = :id_momento, nombre = :nombre, descripcion = :descripcion, color = :color WHERE id_grupo = :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':id_momento', $id_momento, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':color', $color);
        if ($imagen !== null) {
            $stmt->bindParam(':imagen', $imagen);
        }
        $res = $stmt->execute();
        if ($res) {
            $this->asignarCategorias($id, $id_momento, $categorias_ids);
        }
        return $res;
    }

    public function asignarCategorias($id_grupo, $id_momento, $categorias_ids = []) {
        $id_grupo = (int)$id_grupo;
        $id_momento = (int)$id_momento;
        if (empty($categorias_ids)) {
            $stmt = $this->conn->prepare("UPDATE categoria SET id_grupo = NULL WHERE id_grupo = :id_grupo");
            $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $cleanIds = array_map('intval', $categorias_ids);
            $placeholders = implode(',', $cleanIds);
            $this->conn->exec("UPDATE categoria SET id_grupo = NULL WHERE id_grupo = {$id_grupo} AND id_categoria NOT IN ({$placeholders})");
            $this->conn->exec("UPDATE categoria SET id_grupo = {$id_grupo}, id_momento = {$id_momento} WHERE id_categoria IN ({$placeholders})");
        }
    }

    public function deleteGrupMoment($id) {
        $query = "UPDATE grupo_momento SET activo = 0 WHERE id_grupo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function toggleStatus($id, $estado = null) {
        if ($estado !== null) {
            $query = "UPDATE grupo_momento SET activo = :estado WHERE id_grupo = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        } else {
            $query = "UPDATE grupo_momento SET activo = CASE WHEN COALESCE(activo,1) = 1 THEN 0 ELSE 1 END WHERE id_grupo = :id";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getGrupoById($id) {
        $query = "SELECT gm.*, mc.nombre AS momento
                  FROM grupo_momento gm
                  INNER JOIN momento_consumo mc ON gm.id_momento = mc.id_momento
                  WHERE gm.id_grupo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>