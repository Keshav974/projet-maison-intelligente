<?php
// Informations de connexion à la base de données PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'postgres'; // VOTRE nom de base de données
$user_db = 'postgres';           // VOTRE utilisateur PostgreSQL
$password_db = 'Keshav.974'; // VOTRE mot de passe

// Construction du DSN (Data Source Name)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

// Options de connexion PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Création de l'instance PDO dans un bloc try-catch
try {
    $db = new PDO($dsn, $user_db, $password_db, $options);
} catch (PDOException $e) {
    // En cas d'erreur de connexion, on affiche un message et on arrête le script
    // En production, il faudrait logguer cette erreur plutôt que de l'afficher
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>