<?php
// Calculate path to assets based on file location
$currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$projectRoot = str_replace('\\', '/', dirname(__DIR__)); // Go up from includes/ to bsm_web/
$relativePath = str_replace($projectRoot, '', $currentDir);
$relativePath = trim($relativePath, '/');
$segments = $relativePath === '' ? [] : explode('/', $relativePath);
$depth = count($segments);
$assetBase = $depth > 0 ? str_repeat('../', $depth) : './';
?>
    <footer class="pc-footer">
      <div class="footer-wrapper container-fluid mx-10">
        <div class="grid grid-cols-12 gap-1.5">
          <div class="col-span-12 sm:col-span-6 my-1">
            <p class="m-0 text-muted">© <?php echo date('Y'); ?> PT Borneo Sarana Margasana. Sistem Monitoring dan Maintenance Kendaraan.</p>
          </div>
          <div class="col-span-12 sm:col-span-6 my-1 justify-self-end">
            <p class="inline-block max-sm:mr-3 sm:ml-2 text-muted">Kontak dukungan: <a href="mailto:support@bsm.co.id">support@bsm.co.id</a></p>
          </div>
        </div>
      </div>
    </footer>
    <script src="<?php echo $assetBase; ?>assets/js/plugins/simplebar.min.js"></script>
    <script src="<?php echo $assetBase; ?>assets/js/plugins/popper.min.js"></script>
    <script src="<?php echo $assetBase; ?>assets/js/icon/custom-icon.js"></script>
    <script src="<?php echo $assetBase; ?>assets/js/plugins/feather.min.js"></script>
    <script src="<?php echo $assetBase; ?>assets/js/component.js"></script>
    <script>window.assetBase = '<?php echo $assetBase; ?>';</script>
    <script src="<?php echo $assetBase; ?>assets/js/theme.js"></script>
    <script src="<?php echo $assetBase; ?>assets/js/script.js"></script>
    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]"></div>
    <script>
      // Set default theme explicitly to light
      layout_change('light');
    </script>
    <script>
      // Sidebar theme flag expects 'true' or 'false'
      layout_theme_sidebar_change('false');
    </script>
    <script>
      change_box_container('false');
    </script>
    <script>
      layout_caption_change('true');
    </script>
    <script>
      layout_rtl_change('false');
    </script>
    <script>
      preset_change('preset-1');
    </script>
    <script>
      main_layout_change('vertical');
    </script>
  </body>
</html>
