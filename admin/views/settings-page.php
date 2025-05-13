<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>Curiosity Generator Settings</h1>
    
    <?php settings_errors(); ?>
    
    <h2 class="nav-tab-wrapper">
        <a href="#tab-api" class="nav-tab nav-tab-active">API Settings</a>
        <a href="#tab-ads" class="nav-tab">Ads Settings</a>
        <a href="#tab-credits" class="nav-tab">Credits Settings</a>
        <a href="#tab-general" class="nav-tab">General Settings</a>
    </h2>
    
    <form method="post" action="options.php">
        <?php settings_fields('cg_settings_group'); ?>
        
        <div id="tab-api" class="tab-content">
            <?php require_once CG_PLUGIN_DIR . 'admin/views/partials/tab-api.php'; ?>
        </div>
        
        <div id="tab-ads" class="tab-content" style="display:none">
            <?php require_once CG_PLUGIN_DIR . 'admin/views/partials/tab-ads.php'; ?>
        </div>
        
        <div id="tab-credits" class="tab-content" style="display:none">
            <?php require_once CG_PLUGIN_DIR . 'admin/views/partials/tab-credits.php'; ?>
        </div>
        
        <div id="tab-general" class="tab-content" style="display:none">
            <?php require_once CG_PLUGIN_DIR . 'admin/views/partials/tab-general.php'; ?>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>