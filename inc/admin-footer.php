        </div>
    </div>
</div>

<div class="admin-overlay" id="adminOverlay"></div>

<script nonce="<?= e(csp_nonce()) ?>">
window.ADMIN = <?= json_for_script([
    'csrf'  => csrf_token(),
    'base'  => '',
    'flash' => take_flashes(),
]) ?>;
</script>
<script src="<?= e(asset_url('assets/js/admin.js', '../')) ?>" defer></script>
</body>
</html>
