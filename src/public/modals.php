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