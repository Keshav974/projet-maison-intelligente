<?php
session_start();
require_once 'includes/config_db.php';

// Protection Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { die("Accès non autorisé."); }

$user_id_a_modifier = $_GET['id'] ?? null;
if (!$user_id_a_modifier) { header("Location: admin_utilisateurs.php"); exit; }

$errors = [];
$success_message = '';

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $points = $_POST['points'] ?? 0;

    try {
        $sql = "UPDATE utilisateurs SET role = :role, niveau = :niveau, points = :points WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':role' => $role,
            ':niveau' => $niveau,
            ':points' => $points,
            ':id' => $user_id_a_modifier
        ]);
        $success_message = "Le profil a été mis à jour avec succès.";
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la mise à jour du profil.";
    }
}

// Récupération des données de l'utilisateur à modifier pour pré-remplir le formulaire
try {
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $user_id_a_modifier]);
    $utilisateur = $stmt->fetch();
    if (!$utilisateur) { throw new Exception("Utilisateur non trouvé."); }
} catch (Exception $e) {
    die($e->getMessage());
}

require_once 'includes/header.php';
?>
<main class="container mt-4">
    <h1>Modifier l'Utilisateur : <?php echo htmlspecialchars($utilisateur['pseudo']); ?></h1>
    <hr>
    <?php
    if (!empty($success_message)) { echo '<div class="alert alert-success">' . $success_message . '</div>'; }
    if (!empty($errors)) { /* ... affichage des erreurs ... */ }
    ?>
    <form action="admin_modifier_utilisateur.php?id=<?php echo $utilisateur['id']; ?>" method="post">
        <div class="mb-3"><label class="form-label">Pseudo</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($utilisateur['pseudo']); ?>" disabled></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" disabled></div>
        <div class="mb-3"><label for="role" class="form-label">Rôle</label>
            <select class="form-select" id="role" name="role">
                <option value="simple" <?php if($utilisateur['role'] === 'simple') echo 'selected'; ?>>Simple</option>
                <option value="complexe" <?php if($utilisateur['role'] === 'complexe') echo 'selected'; ?>>Complexe</option>
                <option value="admin" <?php if($utilisateur['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select>
        </div>
        <div class="mb-3"><label for="niveau" class="form-label">Niveau</label>
            <select class="form-select" id="niveau" name="niveau">
                <option value="débutant" <?php if($utilisateur['niveau'] === 'débutant') echo 'selected'; ?>>Débutant</option>
                <option value="intermédiaire" <?php if($utilisateur['niveau'] === 'intermédiaire') echo 'selected'; ?>>Intermédiaire</option>
                <option value="avancé" <?php if($utilisateur['niveau'] === 'avancé') echo 'selected'; ?>>Avancé</option>
                <option value="expert" <?php if($utilisateur['niveau'] === 'expert') echo 'selected'; ?>>Expert</option>
            </select>
        </div>
        <div class="mb-3"><label for="points" class="form-label">Points</label><input type="number" class="form-control" id="points" name="points" value="<?php echo htmlspecialchars($utilisateur['points']); ?>"></div>
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="admin_utilisateurs.php" class="btn btn-secondary">Annuler</a>
    </form>
</main>
<?php require_once 'includes/footer.php'; ?>