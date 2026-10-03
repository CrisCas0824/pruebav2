<?php
include_once 'Core/dataBase.php';

class MomentConModel{
    private $conn;

    public function __construct(){
        $dataBase = New DataBase();
        $this->conn = $dataBase->getConnection();
    }

    public function getMomento(){
        $query = "SELECT * FROM momento_consumo ORDER BY id_momento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMomentoById($id){
        $query = "SELECT * FROM momento_consumo WHERE id_momento = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>