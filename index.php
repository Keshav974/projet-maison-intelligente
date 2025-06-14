<?php
session_start();

require_once 'config_db.php';

$recherche = $_GET['recherche'] ?? '';
$type_filtre = $_GET['type'] ?? '';
$marque_filtre = $_GET['marque'] ?? '';

// On détermine si une recherche est active (si au moins un filtre est utilisé).
$is_search_active = !empty($recherche) || !empty($type_filtre) || !empty($marque_filtre);

// On construit la requête SQL de base.
$sql = "SELECT nom, description, type, marque FROM catalogue_objets";

$conditions = [];
$params = [];

// Si une recherche est active, on construit la clause WHERE.

if ($is_search_active) {
    if (!empty($recherche)) {
        $conditions[] = "(nom ILIKE :recherche OR description ILIKE :recherche)";
        $params[':recherche'] = '%' . $recherche . '%';
    }
    if (!empty($type_filtre)) {
        $conditions[] = "type = :type";
        $params[':type'] = $type_filtre;
    }
    if (!empty($marque_filtre)) {
        $conditions[] = "marque = :marque";
        $params[':marque'] = $marque_filtre;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY nom ASC";
} else {
    // SINON (pas de recherche), on affiche les 5 premiers objets du catalogue.
    $sql .= " ORDER BY id ASC LIMIT 5";
}

// Exécution de la requête
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $objets_results = $stmt->fetchAll();
} catch (PDOException $e) {
    $objets_results = [];
}

require_once 'header.php';
?>

<div class="container-fluid px-0">
    <div class="text-center py-5">
        <div class="container">
            <h1 class="display-4 fw-bold">Votre Maison, Plus Intelligente</h1>
            <p class="lead my-4">Centralisez, contrôlez et optimisez tous vos objets connectés depuis une seule et même plateforme intuitive.</p>
            <a href="inscription.php" class="btn btn-primary btn-lg">Créez votre compte gratuitement</a>
            <a href="#free-tour" class="btn btn-outline-secondary btn-lg">Découvrir les fonctionnalités</a>
        </div>
    </div>
</div>

<main class="container mt-5">

    <section id="free-tour" class="text-center">
        <h2 class="mb-5">Une plateforme, des possibilités infinies</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <i class="bi bi-grid-1x2-fill fs-1 text-primary"></i>
                <h4 class="mt-3">Tableau de bord centralisé</h4>
                <p>Visualisez en un coup d'œil l'état de tous vos appareils. Accédez rapidement à vos routines et informations essentielles.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="bi bi-search fs-1 text-primary"></i>
                <h4 class="mt-3">Recherche & Gestion</h4>
                <p>Trouvez, filtrez et gérez facilement n'importe quel objet connecté. Ajoutez de nouveaux appareils en quelques clics.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="bi bi-person-gear fs-1 text-primary"></i>
                <h4 class="mt-3">Gestion des Rôles</h4>
                <p>Débloquez des fonctionnalités avancées en gagnant des points et en faisant évoluer votre niveau d'expertise sur la plateforme.</p>
            </div>
        </div>
    </section>
    
<hr class="my-5">

    <section id="recherche-information">
        <div class="text-center">
            <h2 class="mb-3">Découvrez les appareils compatibles</h2>
            <p class="lead mb-5">Utilisez les filtres pour explorer les types d'objets que vous pouvez intégrer.</p>
        </div>

        <div class="row">
            <div class="col-lg-3">
                <h4>Filtres</h4>
                <div class="card">
                    <div class="card-body">
                        <form action="index.php#recherche-information" method="get">
                            <div class="mb-3">
                                <label for="recherche" class="form-label">Mot-clé</label>
                                <input type="text" class="form-control" id="recherche" name="recherche" placeholder="ex: Ampoule..." value="<?php echo htmlspecialchars($recherche); ?>">
                            </div>
                            <?php
                            // Récupération dynamique des types et marques distincts depuis la base de données
                            try {
                                $types_stmt = $db->query("SELECT DISTINCT type FROM catalogue_objets ORDER BY type ASC");
                                $types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

                                $marques_stmt = $db->query("SELECT DISTINCT marque FROM catalogue_objets ORDER BY marque ASC");
                                $marques = $marques_stmt->fetchAll(PDO::FETCH_COLUMN);
                            } catch (PDOException $e) {
                                $types = [];
                                $marques = [];
                            }
                            ?>
                            <div class="mb-3">
                                <label for="type" class="form-label">Catégorie</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Toutes</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php if ($type_filtre === $type) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="marque" class="form-label">Marque</label>
                                <select class="form-select" id="marque" name="marque">
                                    <option value="">Toutes</option>
                                    <?php foreach ($marques as $marque): ?>
                                        <option value="<?php echo htmlspecialchars($marque); ?>" <?php if ($marque_filtre === $marque) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($marque); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if ($is_search_active) : ?>
                    <h4>Résultats de votre recherche</h4>
                <?php else : ?>
                    <h4>
                        <a href="catalogue.php" class="text-decoration-none">Aperçu du catalogue</a>
                    </h4>
                <?php endif; ?>
                <hr>
                
                <div class="row">
                    <?php if (!empty($objets_results)) : ?>
                        <?php foreach ($objets_results as $objet) : ?>
                            <div class="col-md-6 col-xl-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($objet['nom']); ?></h5>
                                        <div class="mb-2">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($objet['type']); ?></span>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($objet['marque']); ?></span>
                                        </div>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($objet['description']); ?></p>
                                    </div>
                                    </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col">
                            <div class="alert alert-warning">Aucun appareil correspondant à vos critères de recherche n'a été trouvé.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once 'footer.php'; ?>