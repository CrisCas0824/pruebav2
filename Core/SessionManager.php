<?php
require_once __DIR__ . '/dataBase.php';

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;

    public function __construct() {
        $dbInstance = new DataBase();
        $this->db = $dbInstance->getConnection();
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read(string $id): string|false {
        try {
            $stmt = $this->db->prepare("SELECT data FROM sys_sessions WHERE id = ?");
            $stmt->execute([$id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['data'];
            }
            return "";
        } catch (Exception $e) {
            return "";
        }
    }

    #[\ReturnTypeWillChange]
    public function write(string $id, string $data): bool {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'pgsql') {
                $sql = "
                    INSERT INTO sys_sessions (id, data, last_accessed) 
                    VALUES (?, ?, CURRENT_TIMESTAMP) 
                    ON CONFLICT (id) 
                    DO UPDATE SET data = EXCLUDED.data, last_accessed = CURRENT_TIMESTAMP
                ";
            } else {
                // Sintaxis estándar MySQL / MariaDB
                $sql = "
                    INSERT INTO sys_sessions (id, data, last_accessed) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE data = VALUES(data), last_accessed = NOW()
                ";
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $data]);
        } catch (Exception $e) {
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function destroy(string $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM sys_sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function gc(int $maxlifetime): int|false {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $stmt = $this->db->prepare("DELETE FROM sys_sessions WHERE last_accessed < (CURRENT_TIMESTAMP - INTERVAL '1 second' * ?)");
            } else {
                $stmt = $this->db->prepare("DELETE FROM sys_sessions WHERE last_accessed < DATE_SUB(NOW(), INTERVAL ? SECOND)");
            }
            $stmt->execute([$maxlifetime]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }
}
