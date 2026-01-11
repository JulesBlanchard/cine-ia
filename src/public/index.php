<?php
// --- 1. CONFIGURATION & DEPENDANCES ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../MovieRepository.php';
require_once __DIR__ . '/../AiServiceInterface.php';
// On charge les services (Mock et Groq)
require_once __DIR__ . '/../MockAiService.php';
require_once __DIR__ . '/../GroqService.php';

// Connexion BDD
$database = new Database();
$pdo = $database->getConnection();
$movieRepo = new MovieRepository($pdo);

// --- 2. CHOIX DU SERVICE IA ---
// On cherche la clé GROQ
$apiKey = getenv('GROQ_API_KEY');

/** @var AiServiceInterface $aiService */
$aiService = null;
$serviceStatus = "";

// On force la vérification : est-ce que la clé existe ?
if ($apiKey && strlen($apiKey) > 5) {
    // C'EST ICI QUE CA SE JOUE : On appelle GROQ
    $aiService = new GroqService($apiKey);
    $serviceStatus = "⚡ Mode Connecté (Groq Llama 3)";
} else {
    // Sinon on appelle le Mock
    $aiService = new MockAiService();
    $serviceStatus = "🟠 Mode Simulation (Mock)";
}

// --- 3. TRAITEMENT DU FORMULAIRE ---
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood'])) {
    $mood = trim($_POST['mood']);

    if (!empty($mood)) {
        try {
            // A. On interroge l'IA (Celle choisie plus haut)
            $jsonResponse = $aiService->suggestMovie($mood);

            // B. On décode le JSON reçu
            $movieData = json_decode($jsonResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($movieData['title'])) {
                // Si le JSON est cassé, on affiche la réponse brute pour comprendre
                throw new Exception("Réponse IA invalide : " . htmlspecialchars($jsonResponse));
            }

            // C. On sauvegarde
            $movieRepo->save(
                $movieData['title'],
                $mood,
                $movieData['summary'] ?? 'Pas de résumé.'
            );

            header("Location: /public/?success=1");
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// --- 4. RECUPERATION DONNEES ---
$movies = $movieRepo->findAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ciné-IA avec Groq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>🎬 Ciné-IA</h1>
        <span class="badge bg-light text-dark fs-6"><?= $serviceStatus ?></span>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Film ajouté avec succès !</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong>Erreur :</strong> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card bg-secondary mb-5 shadow-lg border-0">
        <div class="card-body p-4">
            <h4 class="card-title mb-3">Quelle est votre envie ce soir ?</h4>
            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="mood" class="form-control form-control-lg"
                           placeholder="Ex: Un film d'action des années 90..." required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">
                    ✨ Demander à Groq
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <?php foreach ($movies as $movie): ?>
            <div class="col-md-4 mb-4">
                <div class="card text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($movie['title']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($movie['user_mood']) ?></h6>
                        <p class="card-text small"><?= htmlspecialchars($movie['summary']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>