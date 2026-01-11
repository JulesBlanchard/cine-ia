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
        // 1. SUPPRIMER
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
            $movieRepo->delete((int)$_POST['id']);
            $_SESSION['flash_message'] = "🗑️ Film supprimé.";
        }

        // 2. MODIFIER (UPDATE)
        elseif (isset($_POST['action']) && $_POST['action'] === 'edit_movie') {
            $id = (int)$_POST['id'];
            $rating = (int)$_POST['rating'];

            // LOGIQUE METIER : Si note > 0, alors forcément Vu. Sinon, on regarde la checkbox.
            $isSeen = ($rating > 0) ? true : isset($_POST['is_seen']);

            $movieRepo->update($id, $rating, $isSeen);
            $_SESSION['flash_message'] = "✅ Fiche mise à jour !";
        }

        // 3. AJOUT MANUEL
        elseif (isset($_POST['action']) && $_POST['action'] === 'add_manual') {
            if (!empty($_POST['title'])) {
                $rating = (int)($_POST['rating'] ?? 0);

                // LOGIQUE METIER : Si note > 0, on force "is_seen" à 1
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

        // 4. DEMANDE IA
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

// --- AFFICHAGE ---
$message = $_SESSION['flash_message'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

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
        <h3 class="fw-bold text-white"><i class="bi bi-collection-play"></i> Ma Collection (<?= count($movies) ?>)</h3>
        <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addMovieModal">
            <i class="bi bi-plus-lg"></i> Ajouter
        </button>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($movies as $movie): ?>
            <div class="col">
                <div class="card h-100 card-movie text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold text-warning mb-0 text-truncate" style="max-width: 80%;" title="<?= htmlspecialchars($movie['title']) ?>">
                                <?= htmlspecialchars($movie['title']) ?>
                            </h5>
                            <?php if (($movie['source_type'] ?? 'AI') === 'AI'): ?>
                                <span class="badge badge-ai" title="IA"><i class="bi bi-robot"></i></span>
                            <?php else: ?>
                                <span class="badge badge-manual" title="Manuel"><i class="bi bi-person"></i></span>
                            <?php endif; ?>
                        </div>

                        <h6 class="card-subtitle mb-3 text-secondary small">
                            <?= htmlspecialchars($movie['director'] ?? 'Inconnu') ?> • <?= htmlspecialchars($movie['release_year'] ?? '') ?>
                        </h6>

                        <div class="mb-3 d-flex align-items-center justify-content-between bg-dark rounded p-2">
                            <div>
                                <?php if ($movie['is_seen']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success"><i class="bi bi-eye-fill"></i> Vu</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary"><i class="bi bi-bookmark"></i> À voir</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-warning text-nowrap">
                                <?php
                                $rating = (int)($movie['personal_rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $rating) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star text-secondary opacity-25"></i>';
                                }
                                ?>
                            </div>
                        </div>

                        <p class="card-text small text-light opacity-75 line-clamp-3">
                            <?= htmlspecialchars($movie['summary'] ?? 'Pas de résumé.') ?>
                        </p>
                    </div>

                    <div class="card-footer bg-transparent border-top border-secondary d-flex justify-content-between align-items-center pt-3">
                        <div>
                            <?php if (!empty($movie['letterboxd_url'])): ?>
                                <a href="<?= htmlspecialchars($movie['letterboxd_url']) ?>" target="_blank" class="btn btn-sm btn-dark border-secondary text-light me-1">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-outline-info border-0"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editMovieModal"
                                    data-id="<?= $movie['id'] ?>"
                                    data-title="<?= htmlspecialchars($movie['title']) ?>"
                                    data-rating="<?= $movie['personal_rating'] ?>"
                                    data-seen="<?= $movie['is_seen'] ?>">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </div>

                        <form method="POST" onsubmit="return confirm('Supprimer ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addMovieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Ajouter un film</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_manual">
                    <div class="mb-3">
                        <label>Titre *</label>
                        <input type="text" name="title" class="form-control bg-secondary text-white border-0" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label>Réalisateur</label><input type="text" name="director" class="form-control bg-secondary text-white border-0"></div>
                        <div class="col-6"><label>Année</label><input type="number" name="year" class="form-control bg-secondary text-white border-0"></div>
                    </div>
                    <div class="mb-3"><label>Résumé</label><textarea name="summary" class="form-control bg-secondary text-white border-0"></textarea></div>

                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_seen" id="addIsSeen">
                                <label class="form-check-label" for="addIsSeen">Vu</label>
                            </div>
                        </div>
                        <div class="col">
                            <select name="rating" id="addRating" class="form-select bg-secondary text-white border-0">
                                <option value="0">Pas de note</option>
                                <option value="5">⭐⭐⭐⭐⭐ Chef d'œuvre</option>
                                <option value="4">⭐⭐⭐⭐ Très bon</option>
                                <option value="3">⭐⭐⭐ Pas mal</option>
                                <option value="2">⭐⭐ Bof</option>
                                <option value="1">⭐ Navet</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMovieModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Modifier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_movie">
                    <input type="hidden" name="id" id="editId">

                    <p class="text-warning fw-bold text-center" id="editTitleDisplay">Titre</p>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Votre note</label>
                        <select name="rating" id="editRating" class="form-select bg-secondary text-white border-0">
                            <option value="0">Pas de note</option>
                            <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                            <option value="4">⭐⭐⭐⭐ (4)</option>
                            <option value="3">⭐⭐⭐ (3)</option>
                            <option value="2">⭐⭐ (2)</option>
                            <option value="1">⭐ (1)</option>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_seen" id="editIsSeen">
                        <label class="form-check-label" for="editIsSeen">Vu</label>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Remplissage Modale EDIT
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

    // 2. AUTO-CHECK "VU" SI NOTE > 0
    function autoCheckSeen(ratingId, checkboxId) {
        const ratingSelect = document.getElementById(ratingId);
        const checkbox = document.getElementById(checkboxId);
        if(ratingSelect && checkbox) {
            ratingSelect.addEventListener('change', function() {
                if (this.value > 0) {
                    checkbox.checked = true;
                }
            });
        }
    }

    // On active cette logique pour les deux formulaires
    autoCheckSeen('addRating', 'addIsSeen');
    autoCheckSeen('editRating', 'editIsSeen');
</script>
</body>
</html>