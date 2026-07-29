        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> Quality Management System — Module Qualité</span>
            <span>v1.0 · PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> · Édition native</span>
        </footer>
    </div>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/app.js"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>
