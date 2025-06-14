<?php
session_start();

// Étape 1 : Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Étape 2 : Vérifier le rôle de l'utilisateur (seul l'admin peut accéder)
if ($_SESSION['role'] !== 'admin') {
    die("Accès non autorisé. Cette page est réservée aux administrateurs.");
}

// Inclure la configuration de la base de données
require_once 'config_db.php';

// Initialiser le tableau des utilisateurs
$utilisateurs = [];

try {
    // Préparer et exécuter la requête pour récupérer tous les utilisateurs
    $sql = "SELECT id, pseudo, email, role, points, niveau 
            FROM utilisateurs 
            ORDER BY id ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    // Récupérer tous les résultats
    $utilisateurs = $stmt->fetchAll();

} catch (PDOException $e) {
    // Gérer les erreurs de requête
    // error_log("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des utilisateurs.";
}

// Inclure l'en-tête
require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Administration - Liste des Utilisateurs</h1>
    <p>Cette page affiche tous les utilisateurs inscrits sur la plateforme.</p>
    <hr>

    <?php
    // Afficher un message d'erreur si la récupération a échoué
    if (isset($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <?php if (!empty($utilisateurs)) : ?>
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
                    <?php foreach ($utilisateurs as $utilisateur) : ?>
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
// Inclure le pied de page
require_once 'footer.php';
?>