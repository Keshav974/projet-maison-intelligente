<?php
session_start();

// Protéger la page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inclure la config BDD
require_once 'config_db.php';

// --- Début de la logique de recherche et de filtrage ---

// Récupérer les valeurs des filtres depuis l'URL (méthode GET)
// L'opérateur '??' est un raccourci pour isset(). Ex: $_GET['type'] ?? '' équivaut à isset($_GET['type']) ? $_GET['type'] : ''
$type_filtre = $_GET['type'] ?? '';
$etat_filtre = $_GET['etat'] ?? '';
$recherche_filtre = $_GET['recherche'] ?? '';

// Construire la requête SQL de base
$sql = "SELECT id, nom, description, type, etat, marque FROM objets_connectes";

// Préparer les conditions WHERE et les paramètres pour la requête préparée
$conditions = [];
$params = [];

if (!empty($type_filtre)) {
    $conditions[] = "type = :type";
    $params[':type'] = $type_filtre;
}

if (!empty($etat_filtre)) {
    $conditions[] = "etat = :etat";
    $params[':etat'] = $etat_filtre;
}

if (!empty($recherche_filtre)) {
    $conditions[] = "(nom ILIKE :recherche OR description ILIKE :recherche)";
    // ILIKE est spécifique à PostgreSQL pour une recherche insensible à la casse. Pour MySQL, ce serait LIKE.
    $params[':recherche'] = '%' . $recherche_filtre . '%';
}

// Si des conditions ont été ajoutées, les joindre à la requête SQL
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Ajouter le tri
$sql .= " ORDER BY nom ASC";

// --- Fin de la logique de recherche et de filtrage ---

// Initialiser le tableau des objets et le message d'erreur
$objets = [];
$error_message = '';

try {
    // Préparer et exécuter la requête SQL dynamique
    $stmt = $db->prepare($sql);
    $stmt->execute($params); // Passer le tableau de paramètres à execute()
    
    $objets = $stmt->fetchAll();

} catch (PDOException $e) {
    // error_log("Erreur lors de la récupération des objets : " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des objets.";
}

// Inclure l'en-tête de la page
require_once 'header.php';
?>

<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Liste des Objets Connectés</h1>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'complexe' || $_SESSION['role'] === 'admin')) : ?>
            <a href="ajouter_objet.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter un objet
            </a>
        <?php endif; ?>
    </div>
    <hr>

    <div class="card bg-light mb-4">
        <div class="card-body">
            <form action="objets.php" method="get" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="recherche" class="visually-hidden">Rechercher</label>
                    <input type="text" class="form-control" id="recherche" name="recherche" placeholder="Rechercher par nom ou description..." value="<?php echo htmlspecialchars($recherche_filtre); ?>">
                </div>
                <div class="col-md-3">
                    <label for="type" class="visually-hidden">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">-- Tous les types --</option>
                        <option value="Thermostat" <?php if ($type_filtre === 'Thermostat') echo 'selected'; ?>>Thermostat</option>
                        <option value="Éclairage" <?php if ($type_filtre === 'Éclairage') echo 'selected'; ?>>Éclairage</option>
                        <option value="Sécurité" <?php if ($type_filtre === 'Sécurité') echo 'selected'; ?>>Sécurité</option>
                        </select>
                </div>
                <div class="col-md-2">
                    <label for="etat" class="visually-hidden">État</label>
                    <select class="form-select" id="etat" name="etat">
                        <option value="">-- Tous les états --</option>
                        <option value="Actif" <?php if ($etat_filtre === 'Actif') echo 'selected'; ?>>Actif</option>
                        <option value="Inactif" <?php if ($etat_filtre === 'Inactif') echo 'selected'; ?>>Inactif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error_message) && !empty($error_message)) : ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

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
                <div class="alert alert-warning">Aucun objet correspondant à vos critères de recherche n'a été trouvé.</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once 'footer.php';
?>