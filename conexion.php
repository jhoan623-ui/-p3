<?php
// conexion.php

class Conexion {
    private $host = "127.0.0.1";
    private $port = "5432";
    private $db_name = "emprendimiento";
    private $username = "usuario";
    private $password = "p@ssw0rd";
    private $conexion;

    // Método para conectar a la base de datos
    public function conectar() {
        $this->conexion = null;
        try {
            $this->conexion = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "<div class='alert error'>⚠️ ERROR CRÍTICO EN LA CONEXIÓN: " . $e->getMessage() . "</div>";
        }
        return $this->conexion;
    }

    // Método para ejecutar consultas SQL sencillas
    public function ejecutarSentencia($sql) {
        try {
            if ($this->conexion == null) {
                $this->conectar();
            }
            if ($this->conexion != null) {
                $stmt = $this->conexion->prepare($sql);
                $stmt->execute();
                return $stmt;
            }
            return false;
        } catch(PDOException $e) {
            echo "Error en la consulta SQL: " . $e->getMessage();
            return false;
        }
    }

    // Método para cerrar la conexión
    public function desconectar() {
        $this->conexion = null;
    }
}
?>