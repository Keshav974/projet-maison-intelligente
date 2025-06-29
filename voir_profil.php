<?php
session_start();
require_once 'includes/config_db.php';

// Protection : seul un utilisateur connecté peut voir les profils.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer l'ID de l'utilisateur à afficher depuis l'URL.
$profil_id = $_GET['id'] ?? null;
if (!$profil_id) {
    header("Location: tableau_de_bord.php"); // Rediriger si aucun ID n'est fourni.
    exit;
}

// Récupérer les informations publiques de l'utilisateur demandé.
$profil_utilisateur = null;
try {
    $sql = "SELECT pseudo, role, points, niveau, to_char(date_inscription, 'DD/MM/YYYY') AS date_inscription_formatee
            FROM utilisateurs 
            WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $profil_id]);
    $profil_utilisateur = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération du profil.";
}

// Vérifier si l'utilisateur connecté est un admin
$is_admin = false;
try {
    $sql_role = "SELECT role FROM utilisateurs WHERE id = :id";
    $stmt_role = $db->prepare($sql_role);
    $stmt_role->execute([':id' => $_SESSION['user_id']]);
    $user_role = $stmt_role->fetchColumn();
    $is_admin = ($user_role === 'admin');
} catch (PDOException $e) {
    $is_admin = false;
}

// Récupérer l'historique d'activité pour l'utilisateur affiché (uniquement si admin)
$logs_activite = [];
if ($is_admin) {
    try {
        $stmt_logs = $db->prepare("
            SELECT 
                type_action, 
                description_action, 
                to_char(date_action, 'DD/MM/YYYY à HH24:MI') AS date_formatee 
            FROM 
                logs_activite 
            WHERE 
                utilisateur_id = :id 
            ORDER BY 
                date_action DESC 
            LIMIT 10
        ");

        $stmt_logs->execute([':id' => $profil_id]);
        $logs_activite = $stmt_logs->fetchAll();
    } catch (PDOException $e) {
        $logs_activite = [];
    }
}

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <?php if (isset($profil_utilisateur) && $profil_utilisateur) : ?>
        <h1>Profil de <?php echo htmlspecialchars($profil_utilisateur['pseudo']); ?></h1>
        <hr>
        <div class="card">
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Pseudonyme :</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($profil_utilisateur['pseudo']); ?></dd>

                    <dt class="col-sm-3">Rôle sur la plateforme :</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars(ucfirst($profil_utilisateur['role'])); ?></dd>

                    <dt class="col-sm-3">Niveau :</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars(ucfirst($profil_utilisateur['niveau'])); ?></dd>
                    
                    <dt class="col-sm-3">Points :</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($profil_utilisateur['points']); ?></dd>
                    
                    <dt class="col-sm-3">Membre depuis le :</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($profil_utilisateur['date_inscription_formatee']); ?></dd>
                </dl>
            </div>
        </div>

        <!-- Bloc d'historique des activités (uniquement pour les admins) -->
        <?php if ($is_admin) : ?>
            <div class="card mt-4">
                <div class="card-header">
                    Historique des dernières activités
                </div>
                <div class="list-group list-group-flush">
                    <?php if (!empty($logs_activite)) : ?>
                        <?php foreach ($logs_activite as $log) : ?>
                            <div class="list-group-item">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $log['type_action']))); ?></strong>
                                </p>
                                <small class="text-muted"><?php echo htmlspecialchars($log['date_formatee']); ?></small>
                                <?php if (!empty($log['description_action'])) : ?>
                                    <p class="mb-0 small fst-italic">
                                        Détail : <?php echo htmlspecialchars($log['description_action']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="list-group-item">Aucune activité enregistrée.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif (isset($error_message)) : ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php else : ?>
        <div class="alert alert-warning">Cet utilisateur n'a pas été trouvé.</div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="membres.php" class="btn btn-secondary">Retour à la liste des membres</a>
    </div>
</main>

<?php require_once 'footer.php'; ?>