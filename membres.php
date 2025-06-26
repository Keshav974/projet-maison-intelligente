<?php
session_start();
require_once 'includes/config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$liste_membres = [];
try {
    $sql = "SELECT id, pseudo, role, niveau FROM utilisateurs ORDER BY pseudo ASC";
    $stmt = $db->query($sql);
    $liste_membres = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération de la liste des membres.";
}

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <h1>Liste des Membres</h1>
    <p>Découvrez les autres membres de la plateforme.</p>
    <hr>

    <?php if (isset($error_message)) : ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="row">
        <?php if (!empty($liste_membres)) : ?>
            <?php foreach ($liste_membres as $membre) : ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-person-circle fs-1"></i>
                            <h5 class="card-title mt-2"><?php echo htmlspecialchars($membre['pseudo']); ?></h5>
                            <p class="card-text">
                                <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($membre['role'])); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($membre['niveau'])); ?></span>
                            </p>
                            <a href="voir_profil.php?id=<?php echo $membre['id']; ?>" class="btn btn-outline-primary">Voir le profil</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col"><p>Aucun membre à afficher.</p></div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'footer.php'; ?>