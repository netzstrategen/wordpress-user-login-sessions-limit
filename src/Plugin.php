<?php

/**
 * @file
 * Contains \Netzstrategen\ConcurrentLogin\Plugin.
 */

namespace Netzstrategen\UserLoginSessionsLimit;

/**
 * @brief The Plugin class provides functionalities to limitate
 * the number of concurrent login attempts into a Wordpress website. It creates
 * a settings page under a specified menu.
 */

class Plugin {

  /**
   * The default number of concurrent logins. This value should be retreived
   * from `concurrent_login_attempts` option from database.
   */
  public static $maximumConcurrentSessions = 1;

  /**
   * The basic constructor for this class.
   *
   * @implements init
   */
  static public function init() {
    add_action('attach_session_information', __CLASS__ . '::attach_session_information');

    $personalizedSettingsEnabled = get_option('concurrent_login_personalized_settings') === '1';
    $personalizedSetting = get_the_author_meta('concurrent_login_attempts_user', get_current_user_id());

    if ($personalizedSettingsEnabled && $personalizedSetting !== '') {
      self::$maximumConcurrentSessions = $personalizedSetting;
    }
    else {
      self::$maximumConcurrentSessions = get_option('concurrent_login_attempts');
    }
    self::$maximumConcurrentSessions = absint(self::$maximumConcurrentSessions);
  }

  /**
   * Hook for plugins_loaded that registers the translation settings
   *
   * @implements plugins_loaded
   */
  static public function plugins_loaded() {
    load_plugin_textdomain('userLoginSessionsLimit', FALSE, dirname( plugin_basename(__FILE__) ) . '/languages/');
  }

  /**
   * Sets initial value for 'last_activity' into session data.
   *
   * @implements attach_session_information
   */
  static public function attach_session_information($session) {
    $session['last_activity'] = time();

    return $session;
  }

  /**
   * Checks if it need to remove a session and update 'last_activity' value.
   *
   * @implements template_redirect
   */
  static public function check_sessions() {
    if (!is_user_logged_in()) {
      return;
    }

    $logged_in_cookie = $_COOKIE[LOGGED_IN_COOKIE];
    if (!$cookie_element = wp_parse_auth_cookie($logged_in_cookie)) {
      return;
    }

    $sessionsAreLimited = self::$maximumConcurrentSessions != 0;
    $sessionsLimitExceeded = self::count() > self::$maximumConcurrentSessions;
    if (self::hasConcurrentSessions() && $sessionsAreLimited && $sessionsLimitExceeded) {
      // If the maximum sessions is reached detect the oldest one and remove it.
      self::removeLessActiveSession();
    }

    $session_manager = \WP_Session_Tokens::get_instance(get_current_user_id());
    $current_session = $session_manager->get($cookie_element['token']);
    // Update the activity data in the DB. Use a delay of 1 min
    // to prevent DB overload.
    if (($current_session['last_activity'] + MINUTE_IN_SECONDS) < time()) {
      $current_session['last_activity'] = time();
      $session_manager->update($cookie_element['token'], $current_session);
    }
  }

  /**
   * Removes the session with less recent activity.
   */
  static private function removeLessActiveSession() {
    $sessions = self::getSessions();
    $user_id = get_current_user_id();
    $oldest = min(wp_list_pluck($sessions, 'login'));
    $min = reset($sessions);
    $min_key = key($sessions);

    if (!isset($min['last_activity'])) {
      $min['last_activity'] = time();
    }
    foreach($sessions as $varifier => $session) {
      if (!isset($session['last_activity'])) {
        $session['last_activity'] = time();
      }
      if($min['last_activity'] > $session['last_activity']) {
        $min = $session;
        $min_key = $varifier;
      }
    }

    unset($sessions[$min_key]);

    if(!empty($sessions)) {
      update_user_meta($user_id, 'session_tokens', $sessions);
    } else {
      delete_user_meta($user_id, 'session_tokens');
    }
  }

  /**
   * Checks if the current user has concurrent session opened in this server.
   *
   * @return {bool} TRUE if this is a logged in user and there are several
   *   sessions FALSE otherwise.
   */
  static private function hasConcurrentSessions() {
    $result = is_user_logged_in() && self::count() > 1;

    return $result;
  }

  /**
   * Returns the number of opened sessions in this server for the current user.
   *
   * @return {int} the number of sessions for the current user.
   */
  static private function count() {
    $result = count(self::getSessions());

    return $result;
  }

  /**
   * Returns the sessions array associated to the current logged in user.
   *
   * @return the session information for the current logged in user.
   */
  static private function getSessions() {
    $user_id  = get_current_user_id();
    $sessions = get_user_meta($user_id, 'session_tokens', TRUE);

    return $sessions;
  }

}
