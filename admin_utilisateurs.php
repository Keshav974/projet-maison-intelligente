<?php
// Page du panneau d'administration pour lister tous les utilisateurs.
session_start();

// On vérifie que l'utilisateur est bien connecté.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Sécurité : on vérifie que seul un administrateur peut accéder à cette page.
if ($_SESSION['role'] !== 'admin') {
    // Si l'utilisateur n'est pas admin, on arrête l'exécution avec un message d'erreur.
    die("Accès non autorisé. Cette page est réservée aux administrateurs.");
}

// Connexion à la base de données.
require_once 'config_db.php';

// Initialisation du tableau qui contiendra les utilisateurs.
$utilisateurs = [];

try {
    // Préparation de la requête SQL pour récupérer tous les utilisateurs, triés par leur ID.
    $sql = "SELECT id, pseudo, email, role, points, niveau 
            FROM utilisateurs 
            ORDER BY id ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    // On récupère tous les résultats dans notre tableau $utilisateurs.
    $utilisateurs = $stmt->fetchAll();

} catch (PDOException $e) {
    // En cas d'erreur avec la base de données, on prépare un message.
    // Pour le débogage, on pourrait enregistrer l'erreur technique : error_log($e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des utilisateurs.";
}

// On inclut l'en-tête commun de la page (début du HTML, navbar, etc.).
require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Administration - Liste des Utilisateurs</h1>
    <p>Cette page affiche tous les utilisateurs inscrits sur la plateforme.</p>
    <hr>

    <?php
    // S'il y a eu une erreur de BDD, on l'affiche ici.
    if (isset($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <?php // On vérifie s'il y a des utilisateurs à afficher avant de construire le tableau.
    if (!empty($utilisateurs)) : ?>
        <div class="table-responsive"> <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Pseudo</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Points</th>
                        <th>Niveau</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($utilisateurs as $utilisateur) : 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($utilisateur['id']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['pseudo']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['role']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['points']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['niveau']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>Aucun utilisateur trouvé dans la base de données.</p>
    <?php endif; ?>

</main>

<?php

require_once 'footer.php';
?>