<?php

/**
 * @file
 * Contains \Netzstrategen\ConcurrentLogin\Admin.
 */

namespace Netzstrategen\UserLoginSessionsLimit;

/**
 * @brief Implements back-end administrative functionality.
 */
class Admin {

  /**
   * The parent admin menu used in 'add_submenu_page' function.
   */
  private static $parentAdminMenu = 'options-general.php';

  /**
   * The avaliability of custom settings for users. This value should be
   * retreived from `concurrent_login_personalized_settings` option.
   */
  private static $personalizedSettings = TRUE;

  /**
   * Registers the options in the admin page for this plugin.
   *
   * @implements admin_init
   */
  static public function admin_init() {
    self::$personalizedSettings = get_option('concurrent_login_personalized_settings') === '1';

    if (self::$personalizedSettings === TRUE) {
      add_action('show_user_profile', __CLASS__ . '::user_profile');
      add_action('edit_user_profile', __CLASS__ .  '::user_profile');
      add_action('personal_options_update', __CLASS__ . '::save_user_profile');
      add_action('edit_user_profile_update', __CLASS__ . '::save_user_profile');
    }

    register_setting(
      'concurrent_login_group',
      'concurrent_login_attempts',
      __CLASS__ . '::sanitize'
    );
    register_setting(
      'concurrent_login_group',
      'concurrent_login_personalized_settings'
    );
  }

  /**
   * Creates the menu for this plugin.
   *
   * @implements admin_menu
   */
  static public function admin_menu() {
    add_submenu_page(
      self::$parentAdminMenu,
      __('User login sessions limit', 'userLoginSessionsLimit'),
      __('User login sessions limit', 'userLoginSessionsLimit'),
      'manage_options',
      'userLoginSessionsLimit',
      __CLASS__ . '::renderOptions'
    );
  }

  /**
   * Sanitizes input data in admin options.
   */
  static public function sanitize($input) {
    $result = absint($input);

    return $result;
  }

  /**
   * Renders options form for plugin main configuration page.
   */
  static public function renderOptions() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'userLoginSessionsLimit'));
    }

    ?>
      <div class="wrap">
      <h1> <?php _e('User login sessions limit', 'userLoginSessionsLimit') ?></h1>
      <hr>
      <form method="post" action="options.php">
    <?php

    settings_fields('concurrent_login_group');
    do_settings_sections('concurrent_login_group');

    ?>
      <table class="form-table">
        <tr>
          <th scope="row"><label for="concurrent_login_attempts"><?php _e('Concurrency', 'userLoginSessionsLimit') ?></label></th>
          <td>
            <input type="number" min="0" id="concurrent_login_attempts" name="concurrent_login_attempts" value="<?php echo esc_attr( get_option('concurrent_login_attempts') ); ?>" >
            <span class="description"><?php _e('Number of concurrent sessions for all users.', 'userLoginSessionsLimit') ?></span>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="concurrent_login_personalized_settings"><?php _e('Personalized settings', 'userLoginSessionsLimit') ?></label></th>
          <td>
            <input type="checkbox" id="concurrent_login_personalized_settings" name="concurrent_login_personalized_settings" value="1" <?php checked(1, get_option('concurrent_login_personalized_settings'), TRUE); ?> >
            <span class="description"><?php _e('Allow the specification of personalized user settings for this plugin.', 'userLoginSessionsLimit') ?></span>
          </td>
        </tr>
      </table>

      <?php submit_button(); ?>

      </form>
      </div>
    <?php
  }

  /**
   * Renders per user options configuration form.
   *
   * @implements show_user_profile
   * @implements edit_user_profile
   */
  static public function user_profile($user) {
    $user_login_attempts = get_user_meta($user->ID, 'concurrent_login_attempts_user', TRUE);
    $global_login_attempts = get_option('concurrent_login_attempts');
    if (isset($user_login_attempts) && $user_login_attempts !== '') {
      $default_value = absint($user_login_attempts);
    }
    else {
      $default_value = esc_attr($global_login_attempts);
    }

    ?>
      <h3><?php _e('Allowed concurrent logins', 'userLoginSessionsLimit') ?></h3>
      <table class="form-table">
        <tr>
          <th scope="row">Concurrency</th>
          <td>
            <input type="number" min='0' name="concurrent_login_attempts_user" id="concurrent_login_attempts_user" value="<?php echo $default_value; ?>" >
            <span class="description"><?php _e('Allow the specification of personalized number of concurrent sessions for this user.', 'userLoginSessionsLimit') ?></span>
          </td>
        </tr>
      </table>
    <?php
  }

  /**
   * Saves plugin options of user profile.
   *
   * @implements personal_options_update
   * @implements edit_user_profile_update
   */
  static public function save_user_profile($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
      return FALSE;
    }

    update_user_meta(
      $user_id,
      'concurrent_login_attempts_user',
      $_POST['concurrent_login_attempts_user']
    );
  }

}
