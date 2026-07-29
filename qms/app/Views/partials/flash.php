<?php
$success = \App\Core\Session::flash('success');
$error = \App\Core\Session::flash('error');
?>
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show app-alert" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show app-alert" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>
