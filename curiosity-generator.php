<?php
/**
 * Plugin Name: Curiosity Generator
 * Plugin URI: https://example.com/curiosity-generator/
 * Description: Generates curiosities on-demand using LLMs via OpenRouter, integrates Adsense, and includes a user credit system.
 * Version: 1.0.0
 * Author: Manus AI Agent
 * Author URI: https://example.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: curiosity-generator
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Define constants for plugin directory and URL.
 */
define('CG_VERSION', '1.0.0');
define('CG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Include the main plugin class.
 */
require_once CG_PLUGIN_DIR . 'includes/class-cg-main.php';

/**
 * Plugin activation and deactivation hooks.
 */
register_activation_hook(__FILE__, array('CG_Main', 'activate'));
register_deactivation_hook(__FILE__, array('CG_Main', 'deactivate'));

/**
 * Initialize the plugin.
 */
function run_curiosity_generator() {
    $plugin = new CG_Main();
    $plugin->run();
}
run_curiosity_generator();
