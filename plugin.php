<?php

/**
 * Plugin Name: User login sessions limit
 * Description: Prevents users from staying logged into the same account from
 * multiple places. Manages different configuration for each user and enables
 * configuration pages for settings.
 *
 * Version: 1.0
 * Author: netzstrategen <http://www.netzstrategen.com/sind>
 * Author: Jairo Rojas-Delgado <https://jairodelgado.github.io>
 * Text Domain: userLoginSessionsLimit
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0
 */

namespace Netzstrategen\UserLoginSessionsLimit;

if (!defined('ABSPATH')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  exit;
}

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class) {
  static $ns_offset;
  if (strpos($class, __NAMESPACE__ . '\\') === 0) {
    if ($ns_offset === NULL) {
      $ns_offset = strlen(__NAMESPACE__) + 1;
    }
    include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
  }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');

register_activation_hook(__FILE__, __NAMESPACE__ . '\Schema::activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\Schema::deactivate');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Schema::uninstall');

add_action('plugins_loaded', __NAMESPACE__ . '\Plugin::plugins_loaded');
add_action('init', __NAMESPACE__ . '\Plugin::init', 20);
add_action('template_redirect', __NAMESPACE__ . '\Plugin::check_sessions', 20);
add_action('admin_menu', __NAMESPACE__ . '\Admin::admin_menu');
add_action('admin_init', __NAMESPACE__ . '\Admin::admin_init');
