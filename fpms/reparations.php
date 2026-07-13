<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Réparations';
$currentPage = 'reparations';

// Palettes à réparer (non conformes ou cassées) + celle éventuellement présélectionnée.
$palettes = $pdo->query(
    "SELECT id, code, etat FROM palettes WHERE etat IN ('non_conforme','cassee') ORDER BY code"
)->fetchAll();
$paletteSel = (int) ($_GET['palette_id'] ?? 0);

$reparations = $pdo->query(
    'SELECT r.*, p.code AS palette_code
       FROM reparations r
       JOIN palettes p ON p.id = r.palette_id
   ORDER BY r.date_reparation DESC, r.id DESC
      LIMIT 100'
)->fetchAll();

$parJour = reparations_par_jour($pdo, 14);

require_once __DIR__ . '/includes/header.php';
?>

<h2>Réparation des palettes</h2>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<div class="charts-grid">
    <div class="card">
        <h3>Enregistrer une réparation</h3>
        <?php if (empty($palettes)): ?>
            <p class="hint">Aucune palette non conforme ou cassée à réparer.</p>
        <?php else: ?>
        <form class="form" method="post" action="/fpms/reparation_save.php" style="box-shadow:none;padding:0;max-width:none">
            <label>Palette *
                <select name="palette_id" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($palettes as $pal): ?>
                        <option value="<?= (int) $pal['id'] ?>" <?= $paletteSel === (int) $pal['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pal['code']) ?> (<?= etat_palette_label($pal['etat']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date de réparation *
                <input type="date" name="date_reparation" value="<?= date('Y-m-d') ?>" required>
            </label>
            <label>Résultat *
                <select name="resultat">
                    <option value="reparee">Réparée (redevient conforme)</option>
                    <option value="irreparable">Irréparable (mise au rebut / cassée)</option>
                </select>
            </label>
            <label>Description
                <textarea name="description" rows="2"></textarea>
            </label>
            <button type="submit">Enregistrer la réparation</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Nombre de réparations par jour (14 j)</h3>
        <canvas id="chartRep"></canvas>
    </div>
</div>

<h3>Historique des réparations</h3>
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Palette</th>
            <th>Résultat</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reparations)): ?>
            <tr><td colspan="4">Aucune réparation enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($reparations as $r): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($r['date_reparation'])) ?></td>
                <td><?= htmlspecialchars($r['palette_code']) ?></td>
                <td>
                    <?php if ($r['resultat'] === 'reparee'): ?>
                        <span class="badge badge-ok">Réparée</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Irréparable</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
histogramme('chartRep', <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($parJour))) ?>,
    <?= json_encode(array_values($parJour)) ?>, '#1971c2', { titre: 'Réparations' });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
