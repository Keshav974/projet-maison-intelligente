<?php
session_start();

// Vérification de la connexion de l'utilisateur.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Vérification que l'utilisateur est un administrateur.
if ($_SESSION['role'] !== 'admin') {
    die("Accès non autorisé. Cette page est réservée aux administrateurs.");
}

// Connexion à la base de données.
require_once 'config_db.php';

// Récupération des utilisateurs depuis la base de données.
$utilisateurs = [];
try {
    $sql = "SELECT id, pseudo, email, role, points, niveau FROM utilisateurs ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Une erreur est survenue lors de la récupération des utilisateurs.";
}

// Inclusion de l'en-tête de la page.
require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Administration - Liste des Utilisateurs</h1>
    <p>Cette page affiche tous les utilisateurs inscrits sur la plateforme.</p>
    <hr>

    <?php
    // Affichage d'un message d'erreur en cas de problème avec la base de données.
    if (isset($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <?php 
    // Affichage du tableau des utilisateurs si des données sont disponibles.
    if (!empty($utilisateurs)) : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
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
// Inclusion du pied de page.
require_once 'footer.php';
?>
