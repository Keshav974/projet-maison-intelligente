<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Page de Test PHP Avancé</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <h1>Test PHP avec Variables et Conditions</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = htmlspecialchars($_POST['var_nom']);
        $email = htmlspecialchars($_POST['var_email']);
        echo "<p> Bonjour, $name d'adresse e-mail $email !</p>";
    }
    ?>
</body>
</html>