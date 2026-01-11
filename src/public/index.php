<?php
// --- CONFIGURATION ---
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../MovieRepository.php';
require_once __DIR__ . '/../AiServiceInterface.php';
require_once __DIR__ . '/../MockAiService.php';
require_once __DIR__ . '/../GroqService.php';

// Connexion & Services
$database = new Database();
$pdo = $database->getConnection();
$movieRepo = new MovieRepository($pdo);

$apiKey = getenv('GROQ_API_KEY');
/** @var AiServiceInterface $aiService */
$aiService = ($apiKey && strlen($apiKey) > 5) ? new GroqService($apiKey) : new MockAiService();
$serviceStatus = ($aiService instanceof GroqService) ? "⚡ IA Connectée" : "🟠 Mode Simulation";

// --- TRAITEMENT DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
            $movieRepo->delete((int)$_POST['id']);
            $_SESSION['flash_message'] = "🗑️ Film supprimé.";
        }
        elseif (isset($_POST['action']) && $_POST['action'] === 'edit_movie') {
            $id = (int)$_POST['id'];
            $rating = (int)$_POST['rating'];
            $isSeen = ($rating > 0) ? true : isset($_POST['is_seen']);
            $movieRepo->update($id, $rating, $isSeen);
            $_SESSION['flash_message'] = "✅ Fiche mise à jour !";
        }
        elseif (isset($_POST['action']) && $_POST['action'] === 'add_manual') {
            if (!empty($_POST['title'])) {
                $rating = (int)($_POST['rating'] ?? 0);
                $isSeen = ($rating > 0) ? 1 : (isset($_POST['is_seen']) ? 1 : 0);
                $movieRepo->save([
                        'title' => trim($_POST['title']),
                        'director' => trim($_POST['director'] ?? ''),
                        'year' => (int)$_POST['year'],
                        'summary' => trim($_POST['summary'] ?? ''),
                        'user_mood' => 'Ajouté manuellement',
                        'source' => 'MANUAL',
                        'is_seen' => $isSeen,
                        'personal_rating' => $rating
                ]);
                $_SESSION['flash_message'] = "💾 Film ajouté manuellement !";
            }
        }
        elseif (isset($_POST['mood'])) {
            $mood = trim($_POST['mood']);
            if (!empty($mood)) {
                $jsonResponse = $aiService->suggestMovie($mood);
                $movieData = json_decode($jsonResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE || !isset($movieData['title'])) {
                    throw new Exception("Format IA invalide.");
                }
                $movieData['mood'] = $mood;
                $movieData['source'] = 'AI';
                $movieData['is_seen'] = 0;
                $movieRepo->save($movieData);
                $_SESSION['flash_message'] = "✨ Film généré : " . $movieData['title'];
            }
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Erreur : " . $e->getMessage();
    }
    header("Location: /public/");
    exit;
}

// --- AFFICHAGE & FILTRAGE ---
$message = $_SESSION['flash_message'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// On récupère tout
$allMovies = $movieRepo->findAll();

// ON SEPARE LES FILMS EN DEUX TABLEAUX
$moviesToWatch = array_filter($allMovies, function($m) { return $m['is_seen'] == 0; });
$moviesSeen = array_filter($allMovies, function($m) { return $m['is_seen'] == 1; });
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
        /* Style des onglets */
        .nav-tabs .nav-link { color: #aaa; border: none; font-weight: bold; }
        .nav-tabs .nav-link.active { color: #ffc107; background: transparent; border-bottom: 3px solid #ffc107; }
        .nav-tabs .nav-link:hover { color: white; }
    </style>
</head>
<body class="bg-dark text-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-film"></i> Ciné-Manager</a>
        <div class="d-flex align-items-center">
            <span class="badge border border-secondary text-secondary me-3"><?= $serviceStatus ?></span>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i><?= $message ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card bg-secondary bg-opacity-10 border border-secondary mb-5">
        <div class="card-body p-4 text-center">
            <h2 class="h4 mb-3 fw-bold">🤖 Laissez l'IA choisir pour vous</h2>
            <form method="POST" class="row justify-content-center g-2">
                <div class="col-md-8">
                    <input type="text" name="mood" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="Ex: Un film de science-fiction philosophique..." required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold w-100"><i class="bi bi-magic"></i> Générer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-white"><i class="bi bi-collection-play"></i> Ma Collection</h3>
        <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addMovieModal">
            <i class="bi bi-plus-lg"></i> Ajouter
        </button>
    </div>

    <ul class="nav nav-tabs mb-4" id="movieTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fs-5" id="towatch-tab" data-bs-toggle="tab" data-bs-target="#towatch-pane" type="button" role="tab">
                🍿 À voir <span class="badge bg-secondary ms-2"><?= count($moviesToWatch) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-5" id="seen-tab" data-bs-toggle="tab" data-bs-target="#seen-pane" type="button" role="tab">
                ✅ Déjà Vu <span class="badge bg-secondary ms-2"><?= count($moviesSeen) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="movieTabsContent">

        <div class="tab-pane fade show active" id="towatch-pane" role="tabpanel">
            <?php if(empty($moviesToWatch)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-camera-reels display-1"></i>
                    <p class="mt-3">Votre liste est vide. Ajoutez des films !</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($moviesToWatch as $movie): ?>
                        <?php include 'movie_card_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="seen-pane" role="tabpanel">
            <?php if(empty($moviesSeen)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-eye-slash display-1"></i>
                    <p class="mt-3">Vous n'avez pas encore vu de films.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($moviesSeen as $movie): ?>
                        <?php include 'movie_card_template.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JS MODALE
    const editModal = document.getElementById('editMovieModal')
    if (editModal) {
        editModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget
            editModal.querySelector('#editId').value = button.getAttribute('data-id')
            editModal.querySelector('#editTitleDisplay').textContent = button.getAttribute('data-title')
            editModal.querySelector('#editRating').value = button.getAttribute('data-rating')
            editModal.querySelector('#editIsSeen').checked = (button.getAttribute('data-seen') == 1)
        })
    }
    // JS AUTO CHECK
    function autoCheckSeen(ratingId, checkboxId) {
        const ratingSelect = document.getElementById(ratingId);
        const checkbox = document.getElementById(checkboxId);
        if(ratingSelect && checkbox) {
            ratingSelect.addEventListener('change', function() {
                if (this.value > 0) checkbox.checked = true;
            });
        }
    }
    autoCheckSeen('addRating', 'addIsSeen');
    autoCheckSeen('editRating', 'editIsSeen');
</script>
</body>
</html>