    </div><!-- /sms-content -->
</div><!-- /wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= getBaseUrl() ?>assets/js/validation.js"></script>
<script>
    // Mobile sidebar toggle
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar   = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992 &&
                sidebar.classList.contains('show') &&
                !sidebar.contains(e.target) &&
                e.target !== toggleBtn &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
</script>
</body>
</html>
