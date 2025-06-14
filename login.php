<?php

session_start();

$host = 'localhost';
$port = '5432';
$dbname = 'postgres'; // Assurez-vous que c'est le bon nom
$user_db = 'postgres';           // Votre utilisateur PostgreSQL
$password_db = 'Keshav.974'; // Votre mot de passe PostgreSQL

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Initialisation des variables
$errors = [];
$identifiant = ""; // Pour le formulaire "collant" (sticky)

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : '';
    $mot_de_passe_login = isset($_POST['mot_de_passe_login']) ? $_POST['mot_de_passe_login'] : '';

    // Validations de base
    if (empty($identifiant)) {
        $errors[] = "Le pseudonyme ou l'email est requis.";
    }
    if (empty($mot_de_passe_login)) {
        $errors[] = "Le mot de passe est requis.";
    }

    // Si pas d'erreurs de validation initiales, tenter la connexion
    if (empty($errors)) {
        try {
            $db = new PDO($dsn, $user_db, $password_db, $options);

            // Préparer la requête pour trouver l'utilisateur par pseudo OU email
            $sql = "SELECT id, pseudo, email, mot_de_passe_hash, role 
                    FROM utilisateurs 
                    WHERE pseudo = :identifiant OR email = :identifiant";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':identifiant', $identifiant);
            $stmt->execute();
            
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur) {
                // Utilisateur trouvé, vérifier le mot de passe
                if (password_verify($mot_de_passe_login, $utilisateur['mot_de_passe_hash'])) {
                    // Mot de passe correct, connexion réussie !
                    $_SESSION['user_id'] = $utilisateur['id'];
                    $_SESSION['pseudo'] = $utilisateur['pseudo'];
                    $_SESSION['role'] = $utilisateur['role'];
                    // Vous pouvez stocker d'autres infos si nécessaire

                    // Rediriger vers le tableau de bord
                    header("Location: tableau_de_bord.php");
                    exit; // Important d'arrêter le script après une redirection
                } else {
                    // Mot de passe incorrect
                    $errors[] = "Identifiant ou mot de passe incorrect.";
                }
            } else {
                // Utilisateur non trouvé
                $errors[] = "Identifiant ou mot de passe incorrect.";
            }

        } catch (PDOException $e) {
            error_log("Erreur de base de données lors de la connexion : " . $e->getMessage());
            $errors[] = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    }
}
?>

<?php require_once 'header.php'; // Inclut votre en-tête HTML et la navbar ?>

<main class="container mt-4">
    <h2>Connexion</h2>

    <?php
    // Afficher les erreurs de validation ou de connexion
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><strong>Erreur(s) :</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>

    <form action="login.php" method="post" class="needs-validation" novalidate>
        <div class="mb-3">
            <label for="identifiant" class="form-label">Pseudonyme ou Email :</label>
            <input type="text" class="form-control" id="identifiant" name="identifiant" value="<?php echo htmlspecialchars($identifiant); ?>" required>
            <div class="invalid-feedback">
                Veuillez fournir votre pseudonyme ou email.
            </div>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe_login" class="form-label">Mot de passe :</label>
            <input type="password" class="form-control" id="mot_de_passe_login" name="mot_de_passe_login" required>
            <div class="invalid-feedback">
                Veuillez fournir votre mot de passe.
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    <p class="mt-3">Pas encore de compte ? <a href="inscription.php">Inscrivez-vous ici !</a></p>
</main>

<?php require_once 'footer.php'; // Inclut votre pied de page HTML et les scripts JS ?>