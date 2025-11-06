<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuración del cliente de Google
$google_client = new Google\Client();

$google_client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$google_client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

$redirect_uri = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] . '/backend/google_callback.php' : 'http://localhost/NexoStock/backend/google_callback.php';
$google_client->setRedirectUri($redirect_uri);

// Alcance: qué información solicitamos a Google
$google_client->addScope("email");
$google_client->addScope("profile");
$google_client->setPrompt('select_account');

?>
