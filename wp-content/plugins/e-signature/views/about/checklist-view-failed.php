<div id="installation-check-fail" class="installation-check-fail">

  <div class='head-check'>
    <div class="check-fail-heading"><b><?php _e("Installation check failed", "esig"); ?></b></div>
    <div class="resolve_error"><?php _e("Resolve the error below then click or refresh the page.", "esig"); ?></div>
  </div>

  <div id="error-show" class="check-fail">

    <?php
    global $is_esig_system_error;
    $memory_limit = WP_MEMORY_LIMIT;
    $php_version = PHP_VERSION;
    $wordpress_version = get_bloginfo('version');


    if (!function_exists('mb_split')) {
      $is_esig_system_error = true; ?>

      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("MBString multibyte support", "esig"); ?> </b></div>
      <div class="failed-check">See <a href="https://wpe.approveme.com/article/mbstring-multibyte-support/" target="_blank"><?php _e("MBString multibyte support for details", "esig"); ?></a></div>

    <?php }

    if ($memory_limit < 96) {
      $is_esig_system_error = true; ?>

      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("Memory limit at least 96M", "esig"); ?></b></div>

      <div class="failed-check"> We recommend trying one of the methods <a href="https://wpe.approveme.com/article/increase-memory-limit/" target="_blank"> in this helpful article </a> to increase your memory limit and improve PDF generation.</div>

    <?php }
    if (version_compare($php_version, '5.4.0', '<')) {
      $is_esig_system_error = true;  ?>

      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("Php version =>5.4 required", "esig"); ?></b></div>
      <div class="failed-check">See <a href="https://wpe.approveme.com/article/php-requirements/" target="_blank"><?php _e("PHP Version Minimum Requirements for details", "esig"); ?></a></div>

    <?php }
    if (version_compare($wordpress_version, '3.5', '<')) {
      $is_esig_system_error = true;  ?>

      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("Wordpress version =>3.5 required", "esig"); ?></b></div>
      <div class="failed-check">See <a href="https://wpe.approveme.com/article/wordpress-version-minimum-requirements/" target="_blank"><?php _e("WordPress Version Minimum Requirements for details", "esig"); ?></a></div>

    <?php }
    if (!function_exists('curl_init')) {
      $is_esig_system_error = true; ?>
      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("PHP Curl not supported", "esig"); ?></b></div>
      <div class="failed-check" style="height: 62px;">See <a href="https://wpe.approveme.com/article/php-curl-installation-instructions/"><?php _e("PHP Curl Install Instructions for details", "esig"); ?></a></div>
    <?php }
    if (!function_exists('openssl_encrypt')) {
      $is_esig_system_error = true; ?>
      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("PHP Openssl not supported", "esig"); ?></b></div>
      <div class="failed-check" style="height: 62px;">See <a href="https://wpe.approveme.com/article/mcrypt-security/"><?php _e("Openssl Security for details", "esig"); ?></a></div>
    <?php }
    $about = new WP_E_aboutsController();
    if (!$about->remote_post_working()) {
      $is_esig_system_error = true; ?>
      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("WP REMOTE GET/POST not working", "esig"); ?></b></div>
      <div class="failed-check">See <a href="https://wpe.approveme.com/article/wp-remote-getpost/"><?php _e("WP REMOTE GET/POST for details", "esig"); ?></a></div>
    <?php }
    if (!extension_loaded('gd')) {
      $is_esig_system_error = true; ?>

      <div class="checklist-failed"> <span class="esig-icon-exit checklist-exit-icon"> </span><b><?php _e("PHP GD Library not enabled", "esig"); ?></b></div>
      <div class="failed-check">See <a href="https://wpe.approveme.com/article/gd-library/"><?php _e("How to Enable the GD Library for details", "esig"); ?></a></div>

    <?php }
    ?>


  </div>

  <div align="center">
    <button id="esig-system-checklist-retry" class="retry-checking"><b>Retry</b></button>
    <a href="#" id="esig-system-checklist-later"><b>I will do it later</b></a>
  </div>
  <div class="line-height"></div>

</div>