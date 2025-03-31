<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'restaurant_app';
    private $username = 'root';
    private $password = '';
    private $connection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->connection = null;

        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}
?>