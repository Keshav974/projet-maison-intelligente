<?php

$host = 'localhost';
$port = '5432';
$dbname = 'postgres';
$user_db = 'postgres';
$password_db = 'Keshav.974';

// Data Source Name pour la connexion PDO
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

// Options de connexion PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db = new PDO($dsn, $user_db, $password_db, $options);
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>