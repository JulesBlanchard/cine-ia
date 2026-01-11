<?php
// --- CONFIGURATION & DEPENDANCES ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../MovieRepository.php';
require_once __DIR__ . '/../AiServiceInterface.php';
require_once __DIR__ . '/../MockAiService.php';
require_once __DIR__ . '/../GroqService.php';

// Connexion BDD
$database = new Database();
$pdo = $database->getConnection();
$movieRepo = new MovieRepository($pdo);

// Service IA
$apiKey = getenv('GROQ_API_KEY');
/** @var AiServiceInterface $aiService */
$aiService = ($apiKey && strlen($apiKey) > 5) ? new GroqService($apiKey) : new MockAiService();
$serviceStatus = ($aiService instanceof GroqService) ? "⚡ IA Connectée" : "🟠 Mode Simulation";

// --- GESTION DES ACTIONS (POST) ---
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. ACTION : SUPPRIMER UN FILM
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $movieRepo->delete((int)$_POST['id']);
        $message = "Film supprimé avec succès.";
    }

    // 2. ACTION : DEMANDER A L'IA
    elseif (isset($_POST['mood'])) {
        $mood = trim($_POST['mood']);
        if (!empty($mood)) {
            try {
                $jsonResponse = $aiService->suggestMovie($mood);
                $movieData = json_decode($jsonResponse, true);

                if (json_last_error() !== JSON_ERROR_NONE || !isset($movieData['title'])) {
                    throw new Exception("L'IA n'a pas renvoyé un format valide.");
                }

                // On ajoute les infos contextuelles
                $movieData['mood'] = $mood;
                $movieData['source'] = 'AI';
                $movieData['is_seen'] = 0;

                $movieRepo->save($movieData);
                $message = "✨ Film ajouté par l'IA : " . htmlspecialchars($movieData['title']);
            } catch (Exception $e) {
                $error = "Erreur IA : " . $e->getMessage();
            }
        }
    }
}

// Récupération de la liste à jour
$movies = $movieRepo->findAll();
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciné-Manager V2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-movie { transition: transform 0.2s; border: none; background: #2b3035; }
        .card-movie:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .badge-ai { background-color: #6f42c1; }
        .badge-manual { background-color: #198754; }
    </style>
</head>
<body class="bg-dark text-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-film"></i> Ciné-Manager
        </a>
        <div class="d-flex align-items-center">
            <span class="badge border border-secondary text-secondary me-3"><?= $serviceStatus ?></span>
            <a href="https://github.com" target="_blank" class="text-white text-decoration-none">
                <i class="bi bi-github"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card bg-secondary bg-opacity-10 border border-secondary mb-5">
        <div class="card-body p-4 text-center">
            <h2 class="h4 mb-3 fw-bold">🤖 Laissez l'IA choisir pour vous</h2>
            <form method="POST" class="row justify-content-center g-2">
                <div class="col-md-8">
                    <input type="text" name="mood" class="form-control form-control-lg bg-dark text-white border-secondary"
                           placeholder="Ex: Un thriller psychologique qui se passe dans l'espace..." required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold w-100">
                        <i class="bi bi-magic"></i> Générer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold"><i class="bi bi-collection-play"></i> Ma Collection (<?= count($movies) ?>)</h3>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($movies as $movie): ?>
            <div class="col">
                <div class="card h-100 card-movie text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold text-warning mb-0">
                                <?= htmlspecialchars($movie['title'] ?? 'Sans titre') ?>
                            </h5>
                            <?php if (($movie['source_type'] ?? 'AI') === 'AI'): ?>
                                <span class="badge badge-ai"><i class="bi bi-robot"></i> IA</span>
                            <?php else: ?>
                                <span class="badge badge-manual"><i class="bi bi-person"></i> Perso</span>
                            <?php endif; ?>
                        </div>

                        <h6 class="card-subtitle mb-3 text-secondary">
                            <?= htmlspecialchars($movie['director'] ?? 'Réalisateur inconnu') ?>
                            • <?= htmlspecialchars($movie['release_year'] ?? '') ?>
                        </h6>

                        <p class="card-text small text-light opacity-75">
                            <?= htmlspecialchars($movie['summary'] ?? 'Pas de résumé disponible.') ?>
                        </p>

                        <?php if (!empty($movie['user_mood'])): ?>
                            <div class="alert alert-dark py-1 px-2 small mb-3">
                                <i class="bi bi-chat-quote"></i> <em>"<?= htmlspecialchars($movie['user_mood']) ?>"</em>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-transparent border-top border-secondary d-flex justify-content-between align-items-center">
                        <?php if (!empty($movie['letterboxd_url'])): ?>
                            <a href="<?= htmlspecialchars($movie['letterboxd_url']) ?>" target="_blank" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-box-arrow-up-right"></i> Letterboxd
                            </a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <form method="POST" onsubmit="return confirm('Supprimer ce film ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>