<?php
// Page du module "Gestion" pour ajouter un objet du catalogue à sa liste personnelle.
session_start();

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] !== 'complexe' && $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé.");
}

// Connexion à la BDD
require_once 'config_db.php';

// --- DÉBUT DE LA LOGIQUE DE LA PAGE ---

$errors = [];
$catalogue_items = [];
$nom_personnalise = "";
$etat_choisi = "Actif";
$catalogue_id_choisi = "";

// --- Logique pour la recherche/filtrage du catalogue (en GET) ---
$recherche_catalogue = $_GET['recherche_catalogue'] ?? '';

// --- Logique pour le traitement de l'ajout (en POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (votre code existant de traitement du formulaire POST reste ici) ...
    // Je le remets ici pour que le fichier soit complet
    $nom_personnalise = trim($_POST['nom_personnalise'] ?? '');
    $catalogue_id_choisi = $_POST['catalogue_id'] ?? '';
    $etat_choisi = $_POST['etat'] ?? 'Actif';

    if (empty($nom_personnalise)) { $errors[] = "Vous devez donner un nom personnalisé à votre objet."; }
    if (empty($catalogue_id_choisi)) { $errors[] = "Vous devez sélectionner un objet dans le catalogue."; }

    if (empty($errors)) {
        try {
            $sql_get_objet_catalogue = "SELECT type, description, marque FROM catalogue_objets WHERE id = :id";
            $stmt_get_objet = $db->prepare($sql_get_objet_catalogue);
            $stmt_get_objet->execute([':id' => $catalogue_id_choisi]);
            $objet_catalogue = $stmt_get_objet->fetch();

            if ($objet_catalogue) {
                $sql_insert = "INSERT INTO objets_connectes (nom, description, type, marque, etat) 
                               VALUES (:nom, :description, :type, :marque, :etat)";
                $stmt_insert = $db->prepare($sql_insert);
                $stmt_insert->execute([
                    ':nom' => $nom_personnalise,
                    ':description' => $objet_catalogue['description'],
                    ':type' => $objet_catalogue['type'],
                    ':marque' => $objet_catalogue['marque'],
                    ':etat' => $etat_choisi
                ]);

                $points_a_ajouter = 5;
                $sql_update_points = "UPDATE utilisateurs SET points = points + :points WHERE id = :user_id";
                $stmt_update_points = $db->prepare($sql_update_points);
                $stmt_update_points->execute([':points' => $points_a_ajouter, ':user_id' => $_SESSION['user_id']]);

                require_once 'functions.php';
                updateUserLevel($_SESSION['user_id'], $db);

                header("Location: objets.php?status=added");
                exit;
            } else {
                $errors[] = "L'objet sélectionné dans le catalogue est invalide.";
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'ajout de l'objet.";
        }
    }
}


// --- On récupère la liste des objets du catalogue pour le menu déroulant (maintenant filtrée) ---
try {
    // On construit la requête dynamiquement
    $sql_catalogue = "SELECT id, nom, marque FROM catalogue_objets";
    $params_catalogue = [];
    if (!empty($recherche_catalogue)) {
        $sql_catalogue .= " WHERE (nom ILIKE :recherche OR description ILIKE :recherche)";
        $params_catalogue[':recherche'] = '%' . $recherche_catalogue . '%';
    }
    $sql_catalogue .= " ORDER BY nom ASC";

    $stmt_catalogue = $db->prepare($sql_catalogue);
    $stmt_catalogue->execute($params_catalogue);
    $catalogue_items = $stmt_catalogue->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Impossible de charger le catalogue d'objets.";
}


require_once 'header.php';
?>

<main class="container mt-4">
    <h1>Ajouter un Objet depuis le Catalogue</h1>
    <p>Choisissez un appareil dans notre catalogue d'objets compatibles et donnez-lui un nom pour l'ajouter à votre maison.</p>
    <hr>

    <div class="card bg-light mb-4">
        <div class="card-body">
            <form action="ajouter_objet.php" method="get">
                <label for="recherche_catalogue" class="form-label"><strong>Rechercher dans le catalogue :</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="recherche_catalogue" name="recherche_catalogue" placeholder="ex: Caméra, Lumière..." value="<?php echo htmlspecialchars($recherche_catalogue); ?>">
                    <button class="btn btn-outline-secondary" type="submit">Chercher</button>
                </div>
            </form>
        </div>
    </div>


    <?php
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $error) { echo '<li>' . htmlspecialchars($error) . '</li>'; }
        echo '</ul></div>';
    }
    ?>

    <form action="ajouter_objet.php" method="post">
        <div class="mb-3">
            <label for="catalogue_id" class="form-label">Appareil du catalogue :</label>
            <select class="form-select" id="catalogue_id" name="catalogue_id" required>
                <option value="">-- Veuillez choisir un appareil --</option>
                <?php foreach ($catalogue_items as $item) : ?>
                    <option value="<?php echo $item['id']; ?>" <?php if($catalogue_id_choisi == $item['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($item['nom']) . ' (' . htmlspecialchars($item['marque']) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="nom_personnalise" class="form-label">Nom personnalisé (ex: "Lumière du Salon") :</label>
            <input type="text" class="form-control" id="nom_personnalise" name="nom_personnalise" value="<?php echo htmlspecialchars($nom_personnalise); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="etat" class="form-label">État initial :</label>
            <select class="form-select" id="etat" name="etat">
                <option value="Actif" <?php if($etat_choisi === 'Actif') echo 'selected'; ?>>Actif</option>
                <option value="Inactif" <?php if($etat_choisi === 'Inactif') echo 'selected'; ?>>Inactif</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Ajouter cet objet à ma maison</button>
        <a href="objets.php" class="btn btn-secondary">Annuler</a>
    </form>
</main>

<?php
require_once 'footer.php';
?>