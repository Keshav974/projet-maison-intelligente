<?php
session_start();

// Protéger la page : vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit;
}

// Inclure le fichier de configuration de la base de données pour obtenir l'objet $db
require_once 'config_db.php';

// Initialiser le tableau des objets
$objets = [];

try {
    // Préparer et exécuter la requête pour récupérer tous les objets
    $sql = "SELECT id, nom, description, type, etat, marque FROM objets_connectes ORDER BY nom ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    // Récupérer tous les résultats sous forme de tableau associatif
    $objets = $stmt->fetchAll();

} catch (PDOException $e) {
    // Gérer les erreurs de requête
    // error_log("Erreur lors de la récupération des objets : " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des objets.";
}

// Inclure l'en-tête de la page
require_once 'header.php';
?>

<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Liste des Objets Connectés</h1>
        </div>
    <hr>

    <?php
    // Afficher un message d'erreur si la récupération a échoué
    if (isset($error_message)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>

    <div class="mb-4 p-3 bg-light rounded">
        <p class="mb-0"><em>La fonctionnalité de recherche et de filtrage sera ajoutée ici demain.</em></p>
    </div>

    <div class="row">
        <?php if (!empty($objets)) : ?>
            <?php foreach ($objets as $objet) : ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($objet['type']); ?></h6>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($objet['description']); ?></p>
                            <p class="card-text"><small class="text-muted">Marque : <?php echo htmlspecialchars($objet['marque']); ?></small></p>
                            <p class="card-text">
                                État : 
                                <?php if ($objet['etat'] === 'Actif') : ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else : ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col">
                <p>Aucun objet connecté trouvé dans la base de données.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Inclure le pied de page
require_once 'footer.php';
?>