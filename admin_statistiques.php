<?php
session_start();
require_once 'includes/config_db.php';
require_once 'includes/functions.php';

// 1. Protection : seul un administrateur peut accéder à cette page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès non autorisé.");
}


try {
    // 2. Récupération des statistiques globales
    $total_utilisateurs = $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    
    // On compte les lignes de type 'connexion'
    $total_connexions = $db->query("SELECT COUNT(*) FROM logs_activite WHERE type_action = 'connexion'")->fetchColumn();
    
    // On compte toutes les autres actions (qui ne sont pas des connexions)
    $total_actions = $db->query("SELECT COUNT(*) FROM logs_activite WHERE type_action = 'ajout_objet'")->fetchColumn();

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

    $graph_labels = [];
$graph_data_connexions = [];
$graph_data_ajouts = [];

try {
    // On récupère le nombre de connexions ET d'ajouts d'objets par jour sur les 30 derniers jours
    $stmt_graph = $db->query("
        SELECT 
            date_trunc('day', date_action)::date AS jour,
            COUNT(*) FILTER (WHERE type_action = 'connexion') AS nombre_connexions,
            COUNT(*) FILTER (WHERE type_action = 'ajout_objet') AS nombre_ajouts
        FROM 
            logs_activite
        WHERE 
            date_action >= NOW() - INTERVAL '30 days'
        GROUP BY 
            jour
        ORDER BY 
            jour ASC
    ");
    $activite_par_jour = $stmt_graph->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activite_par_jour as $jour) {
        $graph_labels[] = date('d/m/Y', strtotime($jour['jour']));
        $graph_data_connexions[] = $jour['nombre_connexions'];
        $graph_data_ajouts[] = $jour['nombre_ajouts'];
    }
} catch (PDOException $e) {
    // Gérer l'erreur, par exemple en initialisant les tableaux à vide
    $graph_labels = $graph_data_connexions = $graph_data_ajouts = [];
}

$duree_labels = [];
$duree_data = [];

try {
    // Cette requête utilise une fonction avancée (LEAD) pour trouver l'événement suivant
    // et calculer la durée entre un état 'Actif' et l'événement d'après.
    $stmt_duree = $db->query("
        WITH logs_ordonnes AS (
            SELECT 
                objet_id, 
                description_action AS etat, 
                date_action,
                LEAD(date_action, 1) OVER (PARTITION BY objet_id ORDER BY date_action) as date_prochaine_action
            FROM logs_activite
            WHERE type_action = 'etat_change'
        )
        SELECT 
            oc.nom AS nom_objet,
            SUM(EXTRACT(EPOCH FROM (date_prochaine_action - date_action))) AS duree_active_secondes
        FROM logs_ordonnes lo
        JOIN objets_connectes oc ON lo.objet_id = oc.id
        WHERE lo.etat = 'Actif' AND lo.date_prochaine_action IS NOT NULL
        GROUP BY oc.nom
        HAVING SUM(EXTRACT(EPOCH FROM (date_prochaine_action - date_action))) > 0
        ORDER BY duree_active_secondes DESC
    ");

    $duree_par_objet = $stmt_duree->fetchAll(PDO::FETCH_ASSOC);

    foreach ($duree_par_objet as $duree) {
        $duree_labels[] = $duree['nom_objet'];
        // On convertit les secondes en minutes pour une meilleure lisibilité
        $duree_data[] = round($duree['duree_active_secondes'] / 60, 2);
    }

} catch (PDOException $e) {
    $duree_labels = $duree_data = [];
}

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
                    <h5 class="card-title">Objets Ajoutés Totaux</h5>
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
                            <th>Objets Ajoutés</th>
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
    <div class="card mb-4">
    <div class="card-header">
        Activité de la plateforme (30 derniers jours)
    </div>
    <div class="card-body">
        <canvas id="platformActivityChart"></canvas>
    </div>
    <div class="card mb-4">
    <div class="card-header">
        Répartition du Temps d'Activité par Objet (en minutes)
    </div>
    <div class="card-body d-flex justify-content-center">
        <div style="position: relative; height:400px; width:400px">
            <canvas id="objectActiveTimeChart"></canvas>
        </div>
    </div>
</div>
</div>
    
</main>
<script>
    const activityLabels = <?php echo json_encode($graph_labels ?? []); ?>;
    const connexionsData = <?php echo json_encode($graph_data_connexions ?? []); ?>;
    const ajoutsData = <?php echo json_encode($graph_data_ajouts ?? []); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('platformActivityChart').getContext('2d');
    
    if (activityLabels.length > 0) {
        const platformActivityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: activityLabels,
                // On définit DEUX jeux de données (datasets)
                datasets: [
                    {
                        label: 'Connexions par jour',
                        data: connexionsData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)', // Bleu
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ajouts d\'objets par jour',
                        data: ajoutsData,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)', // Vert
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                }
            }
        });
    } else {
        ctx.font = "16px Arial";
        ctx.textAlign = "center";
        ctx.fillText("Pas de données d'activité pour les 30 derniers jours.", ctx.canvas.width / 2, 50);
    }
});
</script>
<script>
    const durationLabels = <?php echo json_encode($duree_labels ?? []); ?>;
    const durationData = <?php echo json_encode($duree_data ?? []); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ... (le script du premier graphique reste inchangé) ...

    const ctxDuration = document.getElementById('objectActiveTimeChart');
    if (ctxDuration && durationLabels.length > 0) {
        new Chart(ctxDuration, {
            type: 'doughnut', // Type de graphique : circulaire
            data: {
                labels: durationLabels,
                datasets: [{
                    label: 'Temps Actif (minutes)',
                    data: durationData,
                    backgroundColor: [ // On peut définir un tableau de couleurs
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    hoverOffset: 4
                }]
            }
        });
    } else if (ctxDuration) {
        const ctx = ctxDuration.getContext('2d');
        ctx.font = "16px Arial";
        ctx.textAlign = "center";
        ctx.fillText("Pas de données de temps d'activité.", ctx.canvas.width / 2, ctx.canvas.height / 2);
    }
});
</script>
<?php
require_once 'includes/footer.php';
?>