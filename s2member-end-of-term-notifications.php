<?php
/*
Plugin Name: s2Member End of Term Notifications
Description: Schedule notifications to be sent relative to term expiration
Author: Ethan Clevenger
*/

class s2MemberEOTNotifications {
	private static $self = false;

	public function __construct() {
		add_action('admin_menu', [$this, 'eotnot_admin_menus'], 11);
		add_action('admin_init', [$this, 'eotnot_admin_init']);
	}

	public function eotnot_admin_init() {
		register_setting( 'eotnot_options', 'eotnot_options', [$this, 'eotnot_options_validate'] );
		add_settings_section('eotnot_settings_section', 'Notifications', function() {echo '<p>Nothing here.</p>'; }, 'eotnot');
		add_settings_field('eotnot_reminder_title', 'Title', [$this, 'eotnot_reminder_title_markup'], 'eotnot', 'eotnot_settings_section');
	}

	public function eotnot_reminder_title_markup() {
		$options = get_option('eotnot_options');
		echo "<input id='eotnot_reminder_title' name='eotnot_options[eotnot_reminder_title]' size='40' type='text' value='{$options['eotnot_reminder_title']}' />";
	}

	public function eotnot_options_validate($input) {
		return $input;
	}

	public function eotnot_admin_menus() {
		add_submenu_page('ws-plugin--s2member-start', 'End-of-Term Notifications', 'EOT Notifications', 'manage_options', 'eotnot-options', [$this, 'eotnot_options_html']);
	}

	public function eotnot_options_html() {
		echo '<div class="wrap">
			<h2>Renewal Emails</h2>
			<form action="options.php" method="post">';
				settings_fields('eotnot_options');
				do_settings_sections('eotnot');
				echo '<input class="button button-primary" name="Submit" type="submit" value="';
				esc_attr_e('Save Changes');
				echo '" />
				</form>
		</div>';
	}

	public static function getInstance() {
		if(!self::$self) {
			self::$self = new self();
		}
		return self::$self;
	}
}

s2MemberEOTNotifications::getInstance();

// function set_html_content_type() {
// 	return 'text/html';
// }
//
//
//
// register_deactivation_hook(__FILE__, 'eot_deactivation');
// function eot_deactivation() {
// 	wp_clear_scheduled_hook('s2hack_eot_notify');
// }
// function get_s2member_custom_fields($user_id = '') {
//     $return = array();
//     $user = get_user_option('s2member_custom_fields', $user_id);
//
//     foreach ((array)json_decode($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'], true) as $field) {
//         if (isset($user[$field['id']])) {
//             $return[$field['id']]['label'] = $field['label'];
//
//             if (empty($field['options']))
//                 $return[$field['id']]['value'] = $user[$field['id']];
//             else {
//                 $field['options'] = strpos($field['options'], "\n") ? explode("\n", $field['options']) : (array)$field['options'];
//                 foreach ($field['options'] as $option) {
//                     $option = explode('|', $option);
//                     $options[$option[0]] = $option[1];
//                 }
//                 foreach ((array)$user[$field['id']] as $choice)
//                     $return[$field['id']]['options'][$choice] = $options[$choice];
//             }
//         }
//     }
//     return $return;
// }
// add_action('wp_loaded', 's2hack_eot_notify_set');
// /**/
// function s2hack_eot_notify_set()
// 	{
// 		global $current_user, $wpdb;
//
// 		$seconds_in_week = 604800; # The amount to substract from s2Member's EOT time to get notification time. 604800 = 1 week
// 		/**/
// 		if(!is_user_logged_in()) # If the user isn't logged in, or the Automatic EOT system is disabled...
// 			return; # ...we don't need to do anything
// 		/* Otherwise... */
//
// 		/*Six week notification */
// 		if(current_user_is('s2member_level2')):
// 			$eot = get_user_meta($current_user->ID, $wpdb->prefix.'s2member_auto_eot_time', true);
// 			$fourweek = $eot - ($seconds_in_week * 4);
// 			$twoweek = $eot - ($seconds_in_week * 2);
// 			$oneweek = $eot - ($seconds_in_week);
// 			$oneday = $eot - ($seconds_in_week / 7);
// 			$day_after = $eot + ($seconds_in_week / 7);
// 			/**/
// 			if($fourweek > time()) { # If the Notification EOT times are in the future
// 				update_user_meta($current_user->ID, 's2_eot_fourweek_notify', $fourweek); # Add the four week notification EOT time
// 			}
// 			if($twoweek > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_twoweek_notify', $twoweek); # Add the two week notification EOT time
// 			}
// 			if($oneweek > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_oneweek_notify', $oneweek); # Add the one week notification EOT time
// 			}
// 			if($oneday > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_oneday_notify', $oneday); # Add the one day notification EOT time
// 			}
// 			if($day_after > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_dayafter_notify', $day_after); # Add the day after notification EOT time
// 			}
// 		endif;
// 		if(current_user_is('s2member_level1')) {
// 			$eot = get_user_meta($current_user->ID, $wpdb->prefix.'s2member_auto_eot_time', true);
// 			$twoweek = $eot - ($seconds_in_week * 2);
// 			$oneweek = $eot - ($seconds_in_week);
// 			$oneday = $eot - ($seconds_in_week / 7);
// 			$day_after = $eot + ($seconds_in_week / 7);
// 			/**/
// 			if($twoweek > time()) { # If the Notification EOT times are in the future
// 				update_user_meta($current_user->ID, 's2_eot_twoweek_trial_notify', $twoweek); # Add the two week notification EOT time
// 			}
// 			if($oneweek > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_oneweek_trial_notify', $oneweek); # Add the one week notification EOT time
// 			}
// 			if($oneday > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_oneday_trial_notify', $oneday); # Add the one day notification EOT time
// 			}
// 			if($day_after > time()) {
// 				update_user_meta($current_user->ID, 's2_eot_dayafter_trial_notify', $day_after); # Add the day after notification EOT time
// 			}
// 		}
// 		if(!wp_next_scheduled('s2hack_eot_notify'))
// 			wp_schedule_event(current_time('timestamp'), 'daily', 's2hack_eot_notify');
// 	}
//
// 	add_action('s2hack_eot_notify', 's2hack_eot_notification');
// function s2hack_eot_notification() # Function to be called daily
// {
// 	add_filter( 'wp_mail_content_type', 'set_html_content_type' );
// 	global $wpdb;
// 	$headers[] = 'From: Church and World <subscriptions@churchandworld.com>';
//
// 	//People who are four weeks out
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_fourweek_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_fourweek_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_fourweek_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_fourweek_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//People who are two weeks out
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_twoweek_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		var_dump($eots);
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_twoweek_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_twoweek_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_twoweek_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//People who are one week out
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_oneweek_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_oneweek_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_oneweek_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_oneweek_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//People who are one day out
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_oneday_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_oneday_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_oneday_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_oneday_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//People who expired yesterday
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_dayafter_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_dayafter_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_dayafter_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_dayafter_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//Trials ending in two weeks
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_twoweeks_trial_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_twoweeks_trial_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_twoweeks_trial_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_twoweeks_trial_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//Trials ending in one week
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_oneweek_trial_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_oneweek_trial_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_oneweek_trial_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_oneweek_trial_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//Trials ending in one day
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_oneday_trial_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_oneday_trial_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_oneday_trial_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_oneday_trial_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
//
// 	//Trials that ended yesterday
// 	$n_eot = time();
// 	/**/
// 	if(is_array($eots = $wpdb->get_results("SELECT `user_id` AS `ID` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 's2_eot_dayafter_trial_notify' AND `meta_value` != '' AND `meta_value` <= '".$wpdb->escape($n_eot)."'"))):
// 		foreach($eots as $eot): # We need to loop through all of the IDs to send the data
// 			if(($user_id = $eot->ID) && is_object($user = new WP_User($user_id)) && $user->ID && !$user->has_cap('administrator')): # Set up variables
// 				delete_user_meta($user_id, 's2_eot_dayafter_trial_notify');
// 				$user = get_userdata($user_id);
// 				$fields = get_s2member_custom_fields($user_id);
// 				$nick = get_user_field('cnw_nickname', $user_id);
// 				if($nick != '') {
// 					$sal = $nick;
// 				}
// 				else {
// 					$last = $user->last_name;
// 					$title = get_user_field('title', $user_id);
// 					$sal = $title.' '.$last;
// 				}
// 				$content = str_replace('[salutation]', $sal, nl2br(get_option('churchnworld_dayafter_trial_content')));
// 				$subject = str_replace('[salutation]', $sal, get_option('churchnworld_dayafter_trial_sub'));
// 				wp_mail($user->user_email, $subject, stripslashes($content), $headers);
// 			endif;
// 		endforeach;
// 	endif;
// 	remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
// } ?>
