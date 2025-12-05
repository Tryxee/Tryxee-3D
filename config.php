<?php
// config.php - place les infos sensibles ici
// Remplace les valeurs par les tiennes
$maj_devise_auto = false;
$log_questions_reponses = true;
$log_calculs = true;
$log_update_devises = false;

$host = 'ip_db';
$pdoname = 'db_name';
$username = 'username';
$password = 'password';

// dossier d'uploads (de préférence hors du dossier public)
define('UPLOAD_BASE', __DIR__ . '/uploads');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$pdoname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // n'expose pas d'informations sensibles en prod
    die("Erreur de connexion à la base de données.");
}

// démarrage session global
if (session_status() === PHP_SESSION_NONE) session_start();

// helper: csrf token
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

?>