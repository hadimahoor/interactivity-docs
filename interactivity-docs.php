<?php

/**
 * Plugin Name:       Interactivity Docs
 * Description:        Documentation archive built on the WordPress Interactivity API.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Requires Plugins:  advanced-custom-fields
 * Text Domain:       interactivity-docs
 */

if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('INTERACTIVITY_DOCS_VERSION', '1.0.0');
define('INTERACTIVITY_DOCS_PLUGIN_FILE', __FILE__);
define('INTERACTIVITY_DOCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INTERACTIVITY_DOCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INTERACTIVITY_DOCS_ACF_PATH', INTERACTIVITY_DOCS_PLUGIN_DIR . 'acf-json/');

// Autoloading: prefer Composer (dev), fall back to a built-in PSR-4 loader (zip installs)
$autoload_file = INTERACTIVITY_DOCS_PLUGIN_DIR . 'vendor/autoload.php';

if (file_exists($autoload_file)) {
    require_once $autoload_file;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix   = 'InteractivityDocs\\';
        $base_dir = INTERACTIVITY_DOCS_PLUGIN_DIR . 'src/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require $file;
        }
    });
}

// Activation
register_activation_hook(__FILE__, function () {
    $plugin = new \InteractivityDocs\Plugin();
    $plugin->activate();
});

// Boot on every request
add_action('plugins_loaded', function () {
    $plugin = new \InteractivityDocs\Plugin();
    $plugin->boot();
});
