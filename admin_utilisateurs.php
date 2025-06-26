<?php
session_start();
require_once 'includes/config_db.php';

// Vérification des droits d'accès pour l'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé.");
}

$errors = [];
$success_message = '';

// Gestion des actions POST (suppression ou ajout)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email_a_supprimer'])) {
        try {
            $stmt = $db->prepare("DELETE FROM membres_autorises WHERE email = :email");
            $stmt->execute([':email' => $_POST['email_a_supprimer']]);
            $success_message = "Le membre autorisé a été supprimé de la liste.";
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la suppression du membre autorisé.";
        }
    }

    if (isset($_POST['id_utilisateur_a_supprimer'])) {
        $id_a_supprimer = $_POST['id_utilisateur_a_supprimer'];
        if ($id_a_supprimer == $_SESSION['user_id']) {
            $errors[] = "Vous ne pouvez pas supprimer votre propre compte administrateur.";
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id = :id");
                $stmt->execute([':id' => $id_a_supprimer]);
                $success_message = "L'utilisateur a été supprimé avec succès.";
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la suppression de l'utilisateur.";
            }
        }
    }

    if (isset($_POST['email_a_autoriser'])) {
        $email = trim($_POST['email_a_autoriser']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Le format de l'email est invalide.";
        } else {
            try {
                $stmt_check = $db->prepare("SELECT id FROM membres_autorises WHERE email = :email");
                $stmt_check->execute([':email' => $email]);
                if ($stmt_check->fetch()) {
                    $errors[] = "Cet email est déjà dans la liste des membres autorisés.";
                } else {
                    $stmt_insert = $db->prepare("INSERT INTO membres_autorises (email, statut) VALUES (:email, 'en_attente')");
                    $stmt_insert->execute([':email' => $email]);
                    $success_message = "L'email " . htmlspecialchars($email) . " a été autorisé à s'inscrire.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de l'ajout de l'email.";
            }
        }
    }
}

// Récupération des données pour affichage
try {
    $utilisateurs = $db->query("SELECT id, pseudo, email, role FROM utilisateurs ORDER BY id ASC")->fetchAll();
    $membres_autorises = $db->query("SELECT email, statut FROM membres_autorises ORDER BY email ASC")->fetchAll();
} catch (PDOException $e) { /* Gestion des erreurs */ }

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <h1>Administration des Utilisateurs</h1>
    <hr>

    <?php
    if (!empty($success_message)) { echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>'; }
    if (!empty($errors)) { /* Affichage des erreurs */ }
    ?>

    <div class="row g-5">
        <div class="col-md-5">
            <h3>Autoriser un nouveau membre</h3>
            <div class="card mb-4">
                <div class="card-body">
                    <form action="admin_utilisateurs.php" method="post">
                        <div class="mb-3">
                            <label for="email_a_autoriser" class="form-label">Email du membre à autoriser :</label>
                            <input type="email" class="form-control" name="email_a_autoriser" id="email_a_autoriser" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Autoriser cet email</button>
                    </form>
                </div>
            </div>

            <h4>Membres déjà autorisés</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membres_autorises as $membre) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($membre['email']); ?></td>
                                <td>
                                    <?php if ($membre['statut'] === 'inscrit') : ?>
                                        <span class="badge bg-success">Inscrit</span>
                                    <?php else : ?>
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="admin_utilisateurs.php" method="post" onsubmit="return confirm('Voulez-vous vraiment retirer cette autorisation ?');">
                                        <input type="hidden" name="email_a_supprimer" value="<?php echo htmlspecialchars($membre['email']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Retirer l'autorisation">
                                            <i class="bi bi-trash-fill"></i> Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-7">
            <h3>Utilisateurs Inscrits</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Pseudo</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($utilisateur['id']); ?></td>
                                <td><a href="voir_profil.php?id=<?php echo $utilisateur['id']; ?>"><?php echo htmlspecialchars($utilisateur['pseudo']); ?></a></td>
                                <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                <td><?php echo htmlspecialchars($utilisateur['role']); ?></td>
                                <td>
                                    <a href="voir_profil.php?id=<?php echo $utilisateur['id']; ?>" class="btn btn-info btn-sm">Voir</a>
                                    <a href="admin_modifier_utilisateur.php?id=<?php echo $utilisateur['id']; ?>" class="btn btn-warning btn-sm me-1">Modifier</a>
                                    <?php if ($utilisateur['id'] != $_SESSION['user_id']) : ?>
                                        <form action="admin_utilisateurs.php" method="post" class="d-inline" onsubmit="return confirm('ATTENTION : Supprimer cet utilisateur est définitif. Êtes-vous sûr ?');">
                                            <input type="hidden" name="id_utilisateur_a_supprimer" value="<?php echo $utilisateur['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
