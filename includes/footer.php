<?php
/**
 * footer.php
 * Reusable footer, scripts, and closing tags.
 */
?>
        </main>

        <footer class="text-center text-muted small py-3 border-top">
            &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> Admin Panel. All rights reserved.
        </footer>
    </div><!-- /.main-content -->
</div><!-- /.wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js"></script>
</body>
</html>
