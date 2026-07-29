<?php
require __DIR__ . '/includes/functions.php';
require_permission('report.view');

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();

$pageTitle = 'Rapports';
$activeMenu = 'rapports';
$pageScripts = <<<'HTML'
<script>
document.querySelectorAll('.export-card').forEach(function (card) {
    card.addEventListener('click', function (e) {
        e.preventDefault();
        var params = new URLSearchParams(new FormData(document.getElementById('reportForm'))).toString();
        window.open(this.dataset.url + (params ? '?' + params : ''), this.dataset.target);
    });
});
</script>
HTML;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><p class="page-eyebrow">Module Qualité</p><h2 class="page-title">Rapports &amp; exports</h2></div></div>

<div class="panel">
    <div class="panel-head"><h3><i class="fa-solid fa-filter me-2"></i>Critères du rapport</h3></div>
    <div class="panel-body">
        <form id="reportForm" class="row g-3">
            <div class="col-md-3"><label class="form-label">Du</label><input type="date" name="date_from" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Au</label><input type="date" name="date_to" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Service</label><select name="department_id" class="form-select"><option value="">Tous</option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="form-label">Gravité</label><select name="severity" class="form-select"><option value="">Toutes</option><option value="critique">Critique</option><option value="majeure">Majeure</option><option value="mineure">Mineure</option></select></div>
            <div class="col-md-3"><label class="form-label">Statut</label><select name="status" class="form-select"><option value="">Tous</option><option value="ouverte">Ouverte</option><option value="en_analyse">En analyse</option><option value="action_corrective">Action corrective</option><option value="validation">Validation</option><option value="cloturee">Clôturée</option></select></div>
        </form>
    </div>
</div>

<div class="row g-3 mt-1">
    <?php
    $exports = [
        ['url' => 'export_csv.php',   'icon' => 'fa-file-csv',   'tone' => 'success', 'title' => 'CSV',            'desc' => 'Données séparées par point-virgule', 'target' => '_self'],
        ['url' => 'export_excel.php', 'icon' => 'fa-file-excel', 'tone' => 'success', 'title' => 'Excel',          'desc' => 'Classeur .xls compatible Microsoft Excel', 'target' => '_self'],
        ['url' => 'rapport_print.php','icon' => 'fa-print',      'tone' => 'info',    'title' => 'Impression / PDF', 'desc' => 'Aperçu imprimable, export PDF via le navigateur', 'target' => '_blank'],
    ];
    foreach ($exports as $ex): ?>
        <div class="col-md-4">
            <a href="#" class="export-card panel h-100" data-url="<?= e($ex['url']) ?>" data-target="<?= $ex['target'] ?>" style="display:block;text-decoration:none">
                <div class="panel-body text-center">
                    <div class="kpi-icon mx-auto" style="width:60px;height:60px;font-size:26px;background:var(--<?= $ex['tone'] ?>-soft);color:var(--<?= $ex['tone'] ?>)"><i class="fa-solid <?= $ex['icon'] ?>"></i></div>
                    <h4 class="mt-3 mb-1" style="color:var(--text)"><?= e($ex['title']) ?></h4>
                    <p class="text-muted small mb-0"><?= e($ex['desc']) ?></p>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
