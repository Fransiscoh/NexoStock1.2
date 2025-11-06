<?php
// Cargar variables de entorno para desarrollo local desde .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Configuración de la base de datos usando variables de entorno
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_DATABASE'] ?? 'nexostock';

// Crear conexión
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar el modo de error de PDO a excepción
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Desactivar emulación de consultas preparadas para mayor seguridad
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Hacer la conexión disponible globalmente
    $pdo = $conn;
    
} catch(PDOException $e) {
    // En producción, no mostrar el mensaje de error real
    die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
    
    // Para depuración:
    // die("Error de conexión: " . $e->getMessage());
}
?>