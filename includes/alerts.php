<?php foreach (getFlashes() as $f): ?>
    <div class="alert alert-<?= clean($f['type']) ?> alert-dismissible fade show bua-alert" role="alert">
        <?= clean($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>
