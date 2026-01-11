<?php
// --- 1. LOGIQUE (Traitement) ---
// On traite les données AVANT d'envoyer le moindre HTML au navigateur

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../MovieRepository.php';

// Initialisation
$database = new Database();
$pdo = $database->getConnection();
$movieRepo = new MovieRepository($pdo);

// Gestion du formulaire (POST)
// On vérifie ça tout de suite. Si on doit rediriger, aucun HTML n'a encore été envoyé.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_test') {
    // On ajoute le film
    $movieRepo->save("Matrix " . rand(1, 100), "Sci-Fi", "Un classique du genre.");

    // On redirige (C'est un Header HTTP)
    header("Location: /public/");
    exit; // Toujours mettre exit après une redirection pour arrêter le script net.
}

// Récupération de la liste (pour l'affichage plus bas)
$movies = $movieRepo->findAll();

// --- 2. VUE (Affichage) ---
// À partir d'ici, on envoie du HTML, donc plus de header() possible.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ciné-IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">

<div class="container mt-5">
    <h1 class="mb-4">🎬 Mes Recommandations Ciné-IA</h1>

    <div class="card bg-secondary mb-4">
        <div class="card-body">
            <h5 class="card-title">Ajouter un test manuellement</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_test">
                <button type="submit" class="btn btn-warning">Ajouter un film de test (Matrix)</button>
            </form>
        </div>
    </div>

    <div class="row">
        <?php foreach ($movies as $movie): ?>
            <div class="col-md-4 mb-3">
                <div class="card text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($movie['title']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($movie['user_mood']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($movie['summary']) ?></p>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Ajouté le <?= $movie['created_at'] ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($movies)): ?>
            <p>Aucun film pour l'instant. Cliquez sur le bouton jaune !</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>