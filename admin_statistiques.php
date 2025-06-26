<?php
session_start();
require_once 'includes/config_db.php';
require_once 'includes/functions.php';

// 1. Protection : seul un administrateur peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé.");
}

// ===================================================================
// NOUVELLE LOGIQUE DE RÉCUPÉRATION DES DONNÉES DEPUIS logs_activite
// ===================================================================
try {
    // 2. Récupération des statistiques globales
    $total_utilisateurs = $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    
    // On compte les lignes de type 'connexion'
    $total_connexions = $db->query("SELECT COUNT(*) FROM logs_activite WHERE type_action = 'connexion'")->fetchColumn();
    
    // On compte toutes les autres actions (qui ne sont pas des connexions)
    $total_actions = $db->query("SELECT COUNT(*) FROM logs_activite WHERE type_action <> 'connexion'")->fetchColumn();

    $stats_globales = [
        'utilisateurs' => $total_utilisateurs,
        'connexions' => $total_connexions ?: 0,
        'actions' => $total_actions ?: 0,
        'connexions_moy' => ($total_utilisateurs > 0) ? round($total_connexions / $total_utilisateurs, 2) : 0,
        'actions_moy' => ($total_utilisateurs > 0) ? round($total_actions / $total_utilisateurs, 2) : 0,
    ];

    // 3. Récupération des statistiques par utilisateur avec une seule requête JOIN
    // Le LEFT JOIN assure que même les utilisateurs sans activité apparaissent avec 0.
    // La syntaxe FILTER est spécifique à PostgreSQL et très efficace.
    $stmt_users = $db->query("
        SELECT 
            u.pseudo, u.role, u.niveau, u.points,
            COUNT(l.id) FILTER (WHERE l.type_action = 'connexion') AS nombre_connexions,
            COUNT(l.id) FILTER (WHERE l.type_action <> 'connexion') AS nombre_actions
        FROM 
            utilisateurs u
        LEFT JOIN 
            logs_activite l ON u.id = l.utilisateur_id
        GROUP BY 
            u.id, u.pseudo, u.role, u.niveau, u.points
        ORDER BY 
            nombre_actions DESC, nombre_connexions DESC
    ");
    $utilisateurs_stats = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur lors de la récupération des statistiques : " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<main class="container mt-4">
    <h1>Statistiques de la Plateforme</h1>
    <p>Vue d'ensemble de l'activité des utilisateurs sur la plateforme.</p>
    <hr>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Utilisateurs Inscrits</h5>
                    <p class="display-4 fw-bold"><?php echo $stats_globales['utilisateurs']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Connexions Totales</h5>
                    <p class="display-4 fw-bold"><?php echo $stats_globales['connexions']; ?></p>
                    <p class="card-text text-muted">Moyenne : <?php echo $stats_globales['connexions_moy']; ?> par utilisateur</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Actions Totales</h5>
                    <p class="display-4 fw-bold"><?php echo $stats_globales['actions']; ?></p>
                    <p class="card-text text-muted">Moyenne : <?php echo $stats_globales['actions_moy']; ?> par utilisateur</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Activité par Utilisateur</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Pseudonyme</th>
                            <th>Rôle</th>
                            <th>Niveau</th>
                            <th>Points</th>
                            <th>Connexions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs_stats as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['pseudo']); ?></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($user['niveau'])); ?></span></td>
                                <td><?php echo htmlspecialchars($user['points']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre_connexions']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre_actions']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>