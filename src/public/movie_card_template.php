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