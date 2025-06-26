<?php
session_start();
require_once 'includes/config_db.php';
require_once 'includes/functions.php';

// Vérification des permissions de l'utilisateur
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role'] !== 'complexe' && $_SESSION['role'] !== 'admin') { die("Accès non autorisé."); }

// Initialisation des variables
$errors = [];
$step = 'search'; // Étape actuelle : recherche, résultats ou configuration
$recherche_params = [
    'recherche' => $_GET['recherche'] ?? '',
    'type' => $_GET['type'] ?? '',
    'marque' => $_GET['marque'] ?? ''
];
$is_search_active = !empty($recherche_params['recherche']) || !empty($recherche_params['type']) || !empty($recherche_params['marque']);
$search_results = [];
$objet_selectionne = null;

// Traitement du formulaire POST pour ajouter un objet
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['catalogue_id_final'])) {
    $nom_personnalise = trim($_POST['nom_personnalise'] ?? '');
    $catalogue_id = $_POST['catalogue_id_final']; 

    if (empty($nom_personnalise)) { $errors[] = "Vous devez donner un nom personnalisé."; }
    if (empty($catalogue_id)) { $errors[] = "Erreur : ID de l'objet du catalogue manquant."; }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT * FROM catalogue_objets WHERE id = :id");
            $stmt->execute([':id' => $catalogue_id]);
            $objet_catalogue = $stmt->fetch();

            if ($objet_catalogue) {
                $sql_insert = "INSERT INTO objets_connectes (nom, description, type, marque, catalogue_id, etat, utilisateur_id) 
                               VALUES (:nom, :description, :type, :marque, :catalogue_id, :etat, :utilisateur_id)";
                $stmt_insert = $db->prepare($sql_insert);
                $stmt_insert->execute([
                    ':nom' => $nom_personnalise,
                    ':description' => $objet_catalogue['description'],
                    ':type' => $objet_catalogue['type'],
                    ':marque' => $objet_catalogue['marque'],
                    ':etat' => 'inactif',
                    ':catalogue_id' => $catalogue_id ,
                    ':utilisateur_id' => $_SESSION['user_id']
                ]);

                $points_ajoutes = 10;
                $stmt_update_points = $db->prepare("UPDATE utilisateurs SET points = points + :points WHERE id = :user_id");
                $stmt_update_points->execute([':points' => $points_ajoutes, ':user_id' => $_SESSION['user_id']]);
                
                //updateUserLevel($_SESSION['user_id'], $db);
                logActivity($_SESSION['user_id'], 'ajout_objet', "Ajout de l'objet : " . htmlspecialchars($nom_personnalise), $db);
                incrementerCompteurAction($_SESSION['user_id'], $db); //

                $_SESSION['success_message'] = "L'objet '" . htmlspecialchars($nom_personnalise) . "' a été ajouté avec succès ! Vous avez gagné $points_ajoutes points.";
                header("Location: objets.php?status=added");
                exit;
            } else {
                $errors[] = "L'objet sélectionné dans le catalogue est invalide ou n'a pas été trouvé.";
            }
        } catch (PDOException $e) { 
            $errors[] = "Erreur lors de l'ajout final de l'objet."; 
        }
    }
}

// Traitement de la sélection d'un objet pour configuration
if (isset($_GET['select_id'])) {
    $step = 'configure';
    try {
        $stmt = $db->prepare("SELECT * FROM catalogue_objets WHERE id = :id");
        $stmt->execute([':id' => $_GET['select_id']]);
        $objet_selectionne = $stmt->fetch();
        if (!$objet_selectionne) { $errors[] = "Objet à configurer non trouvé."; $step = 'search'; }
    } catch (PDOException $e) { $errors[] = "Erreur lors de la sélection de l'objet."; $step = 'search'; }
} 
// Traitement de la recherche d'objets dans le catalogue
elseif ($is_search_active) {
    $step = 'results';
    try {
        $sql = "SELECT * FROM catalogue_objets WHERE 1=1";
        $params = [];

        if (!empty($recherche_params['recherche'])) {
            $sql .= " AND (nom ILIKE :recherche OR description ILIKE :recherche)";
            $params[':recherche'] = '%' . $recherche_params['recherche'] . '%';
        }
        if (!empty($recherche_params['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $recherche_params['type'];
        }
        if (!empty($recherche_params['marque'])) {
            $sql .= " AND marque = :marque";
            $params[':marque'] = $recherche_params['marque'];
        }

        $sql .= " ORDER BY nom ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $errors[] = "Erreur lors de la recherche."; }
}

// Chargement des options pour les filtres de recherche
try {
    $type_options = $db->query("SELECT DISTINCT type FROM catalogue_objets WHERE type IS NOT NULL AND type != '' ORDER BY type ASC")->fetchAll(PDO::FETCH_COLUMN);
    $marque_options = $db->query("SELECT DISTINCT marque FROM catalogue_objets WHERE marque IS NOT NULL AND marque != '' ORDER BY marque ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $errors[] = "Erreur de chargement des filtres."; }

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <h1>Ajouter un Objet depuis le Catalogue</h1>
    <hr>
    
    <!-- Affichage des erreurs -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error) { echo '<li>' . htmlspecialchars($error) . '</li>'; } ?></ul></div>
    <?php endif; ?>

    <!-- Formulaire de recherche -->
    <div class="card bg-light mb-4">
        <div class="card-body">
            <h5 class="card-title">Étape 1 : Rechercher un appareil</h5>
            <form action="ajouter_objet.php" method="get">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="recherche" class="form-label">Rechercher par mot-clé</label>
                        <input type="text" class="form-control" id="recherche" name="recherche" placeholder="ex: Caméra, Lumière..." value="<?php echo htmlspecialchars($recherche_params['recherche']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Catégorie</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Toutes</option>
                            <?php foreach ($type_options as $type) : ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php if ($recherche_params['type'] === $type) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="marque" class="form-label">Marque</label>
                        <select class="form-select" id="marque" name="marque">
                            <option value="">Toutes</option>
                             <?php foreach ($marque_options as $marque) : ?>
                                <option value="<?php echo htmlspecialchars($marque); ?>" <?php if ($recherche_params['marque'] === $marque) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($marque); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Affichage des résultats de recherche -->
    <?php if ($step === 'results'): ?>
        <div class="card mb-4">
            <div class="card-header"><h5>Étape 2 : Choisissez un appareil dans les résultats</h5></div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $objet): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($objet['type']); ?></span>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($objet['marque']); ?></span>
                                        </div>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($objet['description']); ?></p>
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="ajouter_objet.php?select_id=<?php echo $objet['id']; ?>" class="btn btn-sm btn-success w-100">Choisir cet appareil</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col">
                            <div class="alert alert-warning">Aucun résultat trouvé pour votre recherche.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulaire de configuration et ajout de l'objet -->
    <?php if ($step === 'configure' && $objet_selectionne): ?>
        <div class="card border-primary">
            <div class="card-header bg-primary text-white"><h5>Étape 3 : Nommez et ajoutez votre appareil</h5></div>
            <div class="card-body">
                <h6>Appareil choisi :</h6>
                <div class="card mb-3"><div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($objet_selectionne['nom']); ?></h5>
                    <p class="card-text mb-1"><small class="text-muted"><?php echo htmlspecialchars($objet_selectionne['description']); ?></small></p>
                </div></div>

                <form action="ajouter_objet.php" method="post">
                    <input type="hidden" name="catalogue_id_final" value="<?php echo htmlspecialchars($objet_selectionne['id']); ?>">
                    <div class="mb-3">
                        <label for="nom_personnalise" class="form-label"><strong>Nom personnalisé :</strong></label>
                        <input type="text" class="form-control" id="nom_personnalise" name="nom_personnalise" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter à ma maison</button>
                    <a href="ajouter_objet.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'footer.php'; ?>
