<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrateur']);

$pageTitle = 'Gestion des utilisateurs';

$users = $pdo->query('SELECT * FROM utilisateurs ORDER BY nom ASC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-people text-primary"></i> Utilisateurs</h1>
    <a href="<?= e(BASE_URL) ?>/users/form.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nouvel utilisateur
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Actif</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($u['nom']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge bg-primary-subtle text-primary-emphasis"><?= e(role_label($u['role'])) ?></span></td>
                        <td>
                            <?php if ($u['actif']): ?>
                                <span class="badge bg-success">Oui</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(BASE_URL) ?>/users/form.php?id=<?= (int) $u['id'] ?>"
                               class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                                <form method="post" action="<?= e(BASE_URL) ?>/users/delete.php" class="d-inline"
                                      onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
