<?php
session_start();
require_once 'config_db.php';
require_once 'functions.php';

// Vérifie que seul un administrateur peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé.");
}

$errors = [];
$success_message = '';

// Gère la soumission du formulaire d'ajout d'appareil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $marque = trim($_POST['marque'] ?? '');

    // Valide les champs obligatoires
    if (empty($nom)) { $errors[] = "Le nom est requis."; }
    if (empty($type)) { $errors[] = "Le type est requis."; }
    if (empty($marque)) { $errors[] = "La marque est requise."; }

    // Si aucune erreur, insère l'appareil dans la base de données
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO catalogue_objets (nom, description, type, marque) VALUES (:nom, :description, :type, :marque)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':description' => $description,
                ':type' => $type,
                ':marque' => $marque
            ]);
            $success_message = "L'appareil '" . htmlspecialchars($nom) . "' a été ajouté au catalogue avec succès !";
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout au catalogue.";
        }
    }
}

require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Administration : Ajouter un Appareil au Catalogue</h1>
    <p>Ce formulaire ajoute un nouvel appareil au catalogue principal, le rendant disponible pour tous les utilisateurs.</p>
    <hr>

    <?php
    // Affiche un message de succès ou les erreurs éventuelles
    if (!empty($success_message)) { echo '<div class="alert alert-success">' . $success_message . '</div>'; }
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) { echo '<li>' . htmlspecialchars($error) . '</li>'; }
        echo '</ul></div>';
    }
    ?>

    <!-- Formulaire d'ajout d'un appareil au catalogue -->
    <div class="card">
        <div class="card-body">
            <form action="admin_ajouter_catalogue.php" method="post">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom de l'appareil :</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description :</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Type :</label>
                    <input type="text" class="form-control" id="type" name="type" placeholder="Ex: Éclairage, Sécurité, Thermostat..." required>
                </div>
                <div class="mb-3">
                    <label for="marque" class="form-label">Marque :</label>
                    <input type="text" class="form-control" id="marque" name="marque" placeholder="Ex: Philips Hue, Ring..." required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter au catalogue</button>
                <a href="tableau_de_bord.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </form>
        </div>
    </div>
</main>

<?php
require_once 'footer.php';
?>