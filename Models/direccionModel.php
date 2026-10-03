<?php
require_once 'Core/dataBase.php';

class DireccionModel{
    private $conn;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getDireccionesByUsuario($idUsuario): array{
        $sql = "SELECT id_direccion, id_usuario, departamento, provincia, distrito, calle, referencia, es_principal, activo
                FROM direccion WHERE id_usuario = :id_usuario AND activo = 1 ORDER BY es_principal DESC, id_direccion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDireccionById($idDireccion, $idUsuario) {
        $sql = "SELECT * FROM direccion WHERE id_direccion = :id_direccion
                  AND id_usuario = :id_usuario AND activo = 1 LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearDireccionCompleta($idUsuario, $departamento, $provincia, $distrito, $calle, $referencia, $esPrincipal, $numero = null, $loteManzana = null, $idDistrito = null) {
        try {
            $this->conn->beginTransaction();

            if ($esPrincipal === 1) {
                $this->quitarPrincipal($idUsuario);
            }

            $sql = "INSERT INTO direccion (id_usuario, departamento, provincia, distrito, calle, numero, lote_manzana, id_distrito, referencia, es_principal, activo)
                    VALUES (:id_usuario, :departamento, :provincia, :distrito, :calle, :numero, :lote_manzana, :id_distrito, :referencia, :es_principal, 1)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':departamento', $departamento ?: 'Lambayeque');
            $stmt->bindValue(':provincia', $provincia ?: 'Chiclayo');
            $stmt->bindValue(':distrito', $distrito);
            $stmt->bindValue(':calle', $calle);
            $stmt->bindValue(':numero', $numero);
            $stmt->bindValue(':lote_manzana', $loteManzana);
            if ($idDistrito) {
                $stmt->bindValue(':id_distrito', (int)$idDistrito, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':id_distrito', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':referencia', $referencia);
            $stmt->bindValue(':es_principal', $esPrincipal, PDO::PARAM_INT);
            
            $stmt->execute();
            $newId = (int)$this->conn->lastInsertId();
            $this->conn->commit();

            return $newId;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function crearDireccion($idUsuario, $departamento, $provincia, $distrito, $calle, $referencia, $esPrincipal): bool {
        try {
            $this->conn->beginTransaction();

            if ($esPrincipal === 1) {
                $this->quitarPrincipal($idUsuario);
            }

            $sql = "INSERT INTO direccion (id_usuario, departamento, provincia, distrito, calle, referencia, es_principal, activo)
                    VALUES (:id_usuario, :departamento, :provincia, :distrito, :calle, :referencia, :es_principal, 1)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':departamento', $departamento);
            $stmt->bindValue(':provincia', $provincia);
            $stmt->bindValue(':distrito', $distrito);
            $stmt->bindValue(':calle', $calle);
            $stmt->bindValue(':referencia', $referencia);
            $stmt->bindValue(':es_principal', $esPrincipal, PDO::PARAM_INT);
            
            $ok = $stmt->execute();
            $this->conn->commit();

            return $ok;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function actualizarDireccion($idDireccion, $idUsuario, $departamento, $provincia, $distrito, $calle, $referencia, $esPrincipal): bool{
        try {
            $this->conn->beginTransaction();

            if ($esPrincipal === 1) {
                $this->quitarPrincipal($idUsuario);
            }

            $sql = "UPDATE direccion SET
                        departamento = :departamento,
                        provincia = :provincia,
                        distrito = :distrito,
                        calle = :calle,
                        referencia = :referencia,
                        es_principal = :es_principal
                    WHERE id_direccion = :id_direccion AND id_usuario = :id_usuario";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':departamento', $departamento);
            $stmt->bindValue(':provincia', $provincia);
            $stmt->bindValue(':distrito', $distrito);
            $stmt->bindValue(':calle', $calle);
            $stmt->bindValue(':referencia', $referencia);
            $stmt->bindValue(':es_principal', $esPrincipal, PDO::PARAM_INT);
            $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

            $ok = $stmt->execute();

            $this->conn->commit();

            return $ok;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function marcarComoPrincipal($idDireccion, $idUsuario): bool {
        try {
            $this->conn->beginTransaction();

            $this->quitarPrincipal($idUsuario);

            $sql = "UPDATE direccion
                    SET es_principal = 1
                    WHERE id_direccion = :id_direccion
                      AND id_usuario = :id_usuario
                      AND activo = 1";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

            $ok = $stmt->execute();

            $this->conn->commit();

            return $ok;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function desactivarDireccion($idDireccion, $idUsuario): bool {
        $sql = "UPDATE direccion
                SET
                    activo = 0,
                    es_principal = 0
                WHERE id_direccion = :id_direccion
                  AND id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function quitarPrincipal($idUsuario): bool{
        $sql = "UPDATE direccion
                SET es_principal = 0
                WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }
}