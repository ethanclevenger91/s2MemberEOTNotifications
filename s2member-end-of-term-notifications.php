<?php
/*
Plugin Name: s2Member End of Term Notifications
Description: Schedule notifications to be sent relative to term expiration
Author: Ethan Clevenger
Version: 1.0.0
*/

class s2MemberEOTNotifications {
	private static $self = false;

	public function __construct() {
		add_action('admin_menu', [$this, 'eotnot_admin_menus'], 11);
		add_action('admin_init', [$this, 'eotnot_admin_init']);

		add_action('wp_ajax_nopriv_eotnot_row_markup', function() {
			$this->eotnot_row_markup($_POST['eotnot_index'], null, true);
		});

		add_action('ws_plugin__s2member_during_auto_eot_system', [$this, 'eotnot_add_expired_meta']);

		register_activation_hook(__FILE__, [$this, 'eotnot_activation']);
		add_action('eotnot_eot_cron', [$this, 'eotnot_send_emails']);

		register_deactivation_hook(__FILE__, [$this, 'eotnot_deactivation']);
	}

	public function eotnot_activation() {
		wp_schedule_event(strtotime('+ 1 minute', time()), 'hourly', 'eotnot_eot_cron');
	}

	public function eotnot_deactivation() {
		wp_clear_scheduled_hook('eotnot_eot_cron');
	}

	public function admin_print_styles() {
		wp_enqueue_style('eotnot_styles');
		wp_enqueue_script('eotnot_scripts');

		wp_localize_script( 'eotnot_scripts', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function eotnot_admin_init() {
		register_setting( 'eotnot_options', 'eotnot_options', [$this, 'eotnot_options_validate'] );
		add_settings_section('eotnot_settings_section', 'Notifications', function() {echo ''; }, 'eotnot');
		add_settings_field('eotnot_from',
			'Send Notifications From <span class="eotnot_instruction">(see <a href="https://codex.wordpress.org/Function_Reference/wp_mail#Valid_Address_Formats" target="_blank">the codex</a> for valid formats)</span>',
			[$this, 'eotnot_from_markup'],
			'eotnot',
			'eotnot_settings_section'
		);
		add_settings_field('eotnot_reminder', '', [$this, 'eotnot_reminder_markup'], 'eotnot', 'eotnot_settings_section');
		add_settings_field('eotnot_end', '', [$this, 'eotnot_end_markup'], 'eotnot', 'eotnot_settings_section');

		wp_register_style( 'eotnot_styles', plugins_url( 'dist/css/styles.css', __FILE__ ) );
		wp_register_script( 'eotnot_scripts', plugins_url( 'dist/js/scripts.js', __FILE__ ) );
	}

	public function eotnot_reminder_markup() {
		$options = get_option('eotnot_options');
		$index = 0;
		foreach($options['eotnot_reminder'] as $notification) {
			echo $this->eotnot_row_markup($index, $notification);
			$index++;
		}
		echo $this->eotnot_row_markup($index);
	}

	public function eotnot_from_markup() {
		$options = get_option('eotnot_options');
		echo "<input id='eotnot_from' placeholder='Me Myself <me@example.net>' name='eotnot_options[eotnot_from]' type='text' value='{$options['eotnot_from']}' />";
		echo "</td></tr></table><table class='eotnot_reminders_table'>";
	}

	public function eotnot_end_markup() {
		echo '</table>';
	}

	public function eotnot_options_validate($input) {
		foreach($input['eotnot_reminder'] as $index => $notification) {
			if($notification['eotnot_reminder_title'] == '') {
				unset($input['eotnot_reminder'][$index]);
			}
		}
		return $input;
	}

	public function eotnot_admin_menus() {
		$page = add_submenu_page('ws-plugin--s2member-start', 'End-of-Term Notifications', 'EOT Notifications', 'manage_options', 'eotnot-options', [$this, 'eotnot_options_html']);
		add_action( 'admin_print_styles-' . $page, [$this, 'admin_print_styles'] );
	}

	public function eotnot_options_html() {
		echo '<div class="wrap">
			<h2>Renewal Emails</h2>
			<form id="eotnot-settings-form" data-parsley-validate action="options.php" method="post">';
				settings_fields('eotnot_options');
				do_settings_sections('eotnot');
				echo '<button type="button" class="eotnot-add-another button button-secondary">Add Another</button><br>';
				echo '<input class="button button-primary" name="Submit" type="submit" value="';
				esc_attr_e('Save Changes');
				echo '" />
				</form>
		</div>';
	}

	public function eotnot_row_markup($index, $notification = null, $ajax = false) {
		ob_start();
		echo "<tr>
			<td>
				<h4 class='eotnot-notification-title".(!$notification ? " eotnot-notification-title--new" : "")."'>".($notification ? $notification['eotnot_reminder_title']."<span class='dashicons dashicons-arrow-right-alt2'></span>" : "New Notification")."</h4>
			</td>
		</tr>
		<tr class='eotnot-notification-content".(!$notification ? " eotnot-notification-content--new" : "")."'>
			<td>
				<table>
					<tr>
						<td>
							<input name='eotnot_options[eotnot_reminder][$index][id]' type='hidden' value='".$index."'>
							<label>Title</label>
						</td>
						<td colspan='4'>
							<input name='eotnot_options[eotnot_reminder][$index][eotnot_reminder_title]' size='40' type='text' value='".($notification ? $notification['eotnot_reminder_title'] : "")."' />
						</td>
					</tr>
					<tr>
						<td>
							<label>Trigger</label>
						</td>
						<td colspan='2'>
							<label class='eotnot-sublabel'>Trigger Amount</label>
							<input name='eotnot_options[eotnot_reminder][$index][eotnot_reminder_trigger_number]' type='number' min='0' value='".($notification ? $notification['eotnot_reminder_trigger_number'] : "")."' />
						</td>
						<td colspan='1'>
							<label class='eotnot-sublabel'>Trigger Unit</label>
							<select name='eotnot_options[eotnot_reminder][$index][eotnot_reminder_trigger_units]'>";
							$unit_options = ['days' => 'Days', 'weeks' => 'Weeks', 'hours' => 'Hours', 'minutes' => 'Minutes'];
							foreach($unit_options as $unit_option => $label) {
									echo "<option ".($notification['eotnot_reminder_trigger_units'] == $unit_option ? "selected " : "")."value='".$unit_option."'>".$label."</option>";
							}
							echo "</select>
						</td>
						<td colspan='1'>
							<label class='eotnot-sublabel'>Trigger Period</label>
							<select name='eotnot_options[eotnot_reminder][$index][eotnot_reminder_trigger_period]'>
								<option value='-'>Before</option>
								<option value='+'>After</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<label>Subject</label>
						</td>
						<td colspan='4'>
							<input name='eotnot_options[eotnot_reminder][$index][eotnot_reminder_subject]' size='40' type='text' value='".($notification ? $notification['eotnot_reminder_subject'] : "")."' />
						</td>
					</tr>
				</table>
				<table>
					<tr>
						<td>
							<label>Email Content</label>";
							echo '<label class="eotnot-sublabel">To use user information in your content, use shortcodes of the format [eot_userinfo="<a href="https://codex.wordpress.org/Function_Reference/get_userdata#Notes" target="_blank">{user_field_here}</a>"].';
							wp_editor(
								($notification ? $notification['eotnot_reminder_content'] : ""),
								"eotnot_wysiwyg_content_$index",
								['textarea_name'=>"eotnot_options[eotnot_reminder][$index][eotnot_reminder_content]"]);
						echo "</td>
					</tr>
				</table>
				<table>
					<tr>
						<td>
							<button type='button' class='button button-primary preview-notification'>Preview Email</button>
						</td>
					</tr>
				</table>
			</td>
		</tr>";
		if(!$ajax) {
			return ob_get_clean();
		} else {
			ob_flush();
			wp_die();
		}
	}

	public function eotnot_send_emails() {
		$users = get_users();
		$time = time();
		foreach($users as $user) {
			if( ( $eot = get_user_field ( 's2member_auto_eot_time', $user->ID ) ) || ( $eot = get_user_meta( $user->ID, 'eotnot_last_expired_at', true ) ) ) {
				$lastEmailSent = get_user_meta($user->ID, 'eotnot_last_sent', true);
				if(!$lastEmailSent) $lastEmailSent = 0;
				$options = get_option('eotnot_options');
				foreach($options['eotnot_reminder'] as $notification) {
					$sendAt = strtotime($notification['eotnot_reminder_trigger_period'].' '.$notification['eotnot_reminder_trigger_number'].' '.$notification['eotnot_reminder_trigger_units'], $eot);
					if($sendAt < $lastEmailSent || $sendAt > $time) {
						continue;
					}
					$sent = $this->eotnot_send_email($notification['eotnot_reminder_subject'], $notification['eotnot_reminder_content'], $user->ID);
					if($sent) {
						update_user_meta($user->ID, 'eotnot_last_sent', $time);
					}
				}
			}
		}
	}

	public function eotnot_send_email($subject, $content, $user_id) {
		return wp_mail(get_userdata($user_id)->user_email, $this->parse_user_shortcodes($subject, $user_id), '<html><body>'.$this->get_email_content($content, $user_id).'</body></html>', ['Content-Type: text/html; charset=UTF-8', 'From: '.get_option('eotnot_options', get_option('admin_email'))['eotnot_from']]);
	}

	public function parse_user_shortcodes($content, $user_id) {
		$userdata = get_userdata($user_id);
		$regex_match = array();
		foreach($userdata->data as $key=>$value) {
			$regex_match[] = "/\[eot_userinfo=\"($key)\"\]/";
		}
		$content = preg_replace_callback($regex_match, function($matches) use ($userdata) {
			return $userdata->$matches[1];
		}, $content);
		return $content;
	}

	public function get_email_content($content, $user_id) {
		return apply_filters('the_content', $this->parse_user_shortcodes($content, $user_id));
	}

	public function eotnot_add_expired_meta($vars) {
		update_user_meta($vars['user']->data->ID, 'eotnot_last_expired_at', $vars['auto_eot_time']);
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
