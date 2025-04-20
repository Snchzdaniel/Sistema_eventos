<?php
// php/config/database.php

// Configuración global para mysqli
$db_host = 'localhost';
$db_name = 'sistema_eventos';
$db_user = 'root';
$db_pass = '';

class Database {
    private $host = 'localhost';
    private $db_name = 'sistema_eventos';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Método para conectar a la base de datos
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo 'Error de conexión: ' . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>