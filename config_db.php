<?php

// Configuration des paramètres de connexion à la base de données
$host = 'localhost';
$port = '5432';
$dbname = 'postgres';
$user_db = 'postgres';
$password_db = 'Keshav.974';

// Construction du Data Source Name (DSN) pour la connexion PDO
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

// Définition des options pour la connexion PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Active le mode d'erreur pour lever des exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Définit le mode de récupération par défaut à un tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Désactive l'émulation des requêtes préparées
];

try {
    // Création de l'objet PDO pour établir la connexion à la base de données
    $db = new PDO($dsn, $user_db, $password_db, $options);
} catch (PDOException $e) {
    // Gestion des erreurs de connexion à la base de données
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>