<?php
/*
Plugin Name: IP2Location Widget
Plugin URI: https://www.ip2location.com/free/widgets
Description: Displays the geolocation information of the visitor who is visiting your website.
Version: 1.2.10
Author: IP2Location
Author URI: http://www.ip2location.com
*/

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
define('IP2LOCATION_WIDGET_ROOT', __DIR__ . DS);

class IP2LocationWidget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'ip2locationwidget',
			__('IP2Location Widget', 'text_domain'),
			['description' => __('IP2Location Widget', 'text_domain')]
		);
	}

	public function init()
	{
		add_action('widgets_init', [&$this, 'register']);
		add_action('admin_menu', [&$this, 'admin_page']);
		add_action('admin_enqueue_scripts', [&$this, 'plugin_enqueues']);
		add_action('wp_ajax_ip2location_widget_submit_feedback', [&$this, 'submit_feedback']);
		add_action('admin_footer_text', [&$this, 'admin_footer_text']);
	}

	public function register()
	{
		register_widget('IP2LocationWidget');
	}

	public function widget($args, $instance)
	{
		$language_code = get_option('ip2location_widget_language');
		$widgettype = get_option('ip2location_widget_type');

		echo $args['before_widget'];

		if (!empty($instance['title'])) {
			echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
		}

		switch ($widgettype) {
			case 'widget-horizontal-png-sample':
				echo '<a href="https://www.ip2location.com/free/widgets" target="_blank"><img src="https://tools.ip2location.com/468x60.png?lang=' . $language_code . '" border="0" width="468" height="60" /></a>';
				break;
			case 'widget-square-png-sample':
				echo '<a href="https://www.ip2location.com/free/widgets" target="_blank"><img src="https://tools.ip2location.com/200x200.png?lang=' . $language_code . '" border="0" width="200" height="200" /></a>';
				break;
			case 'widget-tall-png-sample':
				echo '<a href="https://www.ip2location.com/free/widgets" target="_blank"><img src="https://tools.ip2location.com/160x600.png?lang=' . $language_code . '" border="0" width="160" height="600" /></a>';
				break;
		}

		echo $args['after_widget'];
	}

	public function form($instance)
	{
		$title = !empty($instance['title']) ? $instance['title'] : __('IP2Location Widget', 'text_domain'); ?>
		<p>
		<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e(esc_attr('Title:')); ?></label>
		<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>
		<?php
		echo '<a href="options-general.php?page=' . basename(__FILE__) . '">Go to Settings</a>';
	}

	public function update($new_instance, $old_instance)
	{
		$instance = [];
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

		return $instance;
	}

	public function set_defaults()
	{
		// Initial default settings
		update_option('ip2location_widget_language', 'en_us');
		update_option('ip2location_widget_type', 'widget-horizontal-png-sample');
		update_option('ip2location_widget_debug_log_enabled', 0);
	}

	public function admin_page()
	{
		add_options_page('IP2Location Widget', 'IP2Location Widget', 'edit_pages', 'ip2location-widget', [&$this, 'admin_options']);
	}

	public function admin_options()
	{
		$status = '';

		if (!is_admin()) {
			$this->write_debug_log('Not logged in as administrator. Settings page will not be shown.');

			return;
		}

		$language_selected = (isset($_POST['language_selected'])) ? sanitize_text_field($_POST['language_selected']) : get_option('ip2location_widget_language');
		$widget_type = (isset($_POST['widgetType'])) ? sanitize_text_field($_POST['widgetType']) : get_option('ip2location_widget_type');
		$enable_debug_log = (isset($_POST['submit']) && isset($_POST['enable_debug_log'])) ? 1 : (((isset($_POST['submit']) && !isset($_POST['enable_debug_log']))) ? 0 : get_option('ip2location_widget_debug_log_enabled'));

		if (isset($_POST['widgetType'])) {
			update_option('ip2location_widget_language', $language_selected);
			update_option('ip2location_widget_type', $widget_type);
			update_option('ip2location_widget_debug_log_enabled', $enable_debug_log);

			$this->write_debug_log($widget_type . ' IP2Location Widget type selected.');

			$status .= '
			<div id="message" class="updated">
				<p>Changes saved.</p>
			</div>';
		}

		$languages = [
			'en_US' => 'English',
			'ja'    => '日本語',
			'zh_CN' => '简体中文',
			'zh_TW' => '繁體中文',
			'et'    => 'Eesti keel ',
			'ms'    => 'Malay ',
			'da'    => 'Dansk ',
			'nl'    => 'Nederlands ',
			'ga'    => 'Gaeilge',
			'pt'    => 'Português ',
			'tr'    => 'Türkçe ',
			'it'    => 'Italiano',
			'vi'    => 'Tiếng Việt ',
			'es'    => 'Español ',
			'sv'    => 'Svenska ',
			'ru'    => 'русский язык ',
			'de'    => 'Deutsch ',
			'fr'    => 'français ',
			'fi'    => 'suomen kieli ',
			'cs'    => 'česky ',
			'ar'    => 'العربية',
			'ko'    => '한국어',
		];

		echo '
		<div class="wrap">
			<h2>IP2Location Widget</h2>
			<p>
				IP2Location Widget displays the geolocation information of the visitor who is visiting your website.
			</p>

			<p>&nbsp;</p>

			' . $status . '

			<form id="widget-type" method="post">
				<div style="border-bottom:1px solid #ccc;">
					<h3>Type of widget to be displayed</h3>
				</div>

				<br/><br/>

				<label style="display: inline-block;">Display Language: </label>
				<select name="language_selected" id="language_selected" >';

		foreach ($languages as $key => $value) {
			if (strtolower($key) == $language_selected) {
				echo '<option value="' . strtolower($key) . '" selected> ' . strtoupper($value) . ' </option>';
			} else {
				echo '<option value="' . strtolower($key) . '"> ' . strtoupper($value) . ' </option>';
			}
		}

		echo '
				</select>
				<p>
					<label>
						<input id="widget-horizontal-png" type="radio" name="widgetType" value="widget-horizontal-png-sample"' . (($widget_type == 'widget-horizontal-png-sample') ? ' checked' : '') . ' /> Information Box (PNG 468x60) Horizontal Image
					</label>

					<div id="widget-horizontal-png-sample" style="margin-left:50px;display:none;padding:20px;">
						<a href="https://www.ip2location.com/free/widgets" target="_blank"><img id="widget1" src="https://tools.ip2location.com/468x60.png?lang=' . $language_selected . '" border="0" width="468" height="60" /></a>
					</div>
				</p>

				<p>
					<label>
						<input id="widget-square-png" type="radio" name="widgetType" value="widget-square-png-sample"' . (($widget_type == 'widget-square-png-sample') ? ' checked' : '') . ' /> Information Box (PNG 200x200) Square Image
					</label>

					<div id="widget-square-png-sample" style="margin-left:50px;display:none;padding:20px;">
						<a href="https://www.ip2location.com/free/widgets" target="_blank"><img id="widget2" src="https://tools.ip2location.com/200x200.png?lang=' . $language_selected . '" border="0" width="200" height="200" /></a>
					</div>
				</p>

				<p>
					<label>
						<input id="widget-tall-png" type="radio" name="widgetType" value="widget-tall-png-sample"' . (($widget_type == 'widget-tall-png-sample') ? ' checked' : '') . ' /> Information Box (PNG 160x600) Tall Image
					</label>

					<div id="widget-tall-png-sample" style="margin-left:50px;display:none;padding:20px;">
						<a href="https://www.ip2location.com/free/widgets" target="_blank"><img id="widget3" src="https://tools.ip2location.com/160x600.png?lang=' . $language_selected . '" border="0" width="160" height="600" /></a>
					</div>
				</p>

				<p>&nbsp;</p>

				<div style="border-bottom:1px solid #ccc;">
					<h3>Settings</h3>
				</div>

				<p>
					<label for="enable_debug_log">
						<input type="checkbox" name="enable_debug_log" id="enable_debug_log"' . (($enable_debug_log) ? ' checked' : '') . '>
						Enable debug log for development purpose.
					</label>
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
				</p>
			</form>

			<p>&nbsp;</p>

				<script language="Javascript">
					jQuery("#widget-horizontal-png").click(function() {
						jQuery("#widget-square-png-sample").hide();
						jQuery("#widget-tall-png-sample").hide();
						jQuery("#widget-horizontal-png-sample").show();
						jQuery("html, body").animate({
							scrollTop: jQuery("#widget-horizontal-png").offset().top - 50
						}, 100);
					});

					jQuery("#widget-square-png").click(function() {
						jQuery("#widget-horizontal-png-sample").hide();
						jQuery("#widget-tall-png-sample").hide();
						jQuery("#widget-square-png-sample").show();
						jQuery("html, body").animate({
							scrollTop: jQuery("#widget-square-png").offset().top - 50
						}, 100);
					});

					jQuery("#widget-tall-png").click(function() {
						jQuery("#widget-horizontal-png-sample").hide();
						jQuery("#widget-square-png-sample").hide();
						jQuery("#widget-tall-png-sample").show();
						jQuery("html, body").animate({
							scrollTop: jQuery("#widget-tall-png").offset().top - 50
						}, 100);
					});

					jQuery("#' . $widget_type . '").show();

					jQuery("#language_selected").change(function(event) {
						var lang_code1 = jQuery("#language_selected").val();
						jQuery("#widget1").attr("src","https://tools.ip2location.com/468x60.png?lang=" + lang_code1);
						jQuery("#widget2").attr("src","https://tools.ip2location.com/200x200.png?lang=" + lang_code1);
						jQuery("#widget3").attr("src","https://tools.ip2location.com/160x600.png?lang=" + lang_code1);
					});
				</script>


			<p>If you like this plugin, please leave us a <a href="https://wordpress.org/support/view/plugin-reviews/ip2location-widget">5 stars rating</a>. Thank You!</p>
		</div>';
	}

	public function write_debug_log($message)
	{
		if (!get_option('ip2location_widget_debug_log_enabled')) {
			return;
		}

		file_put_contents(IP2LOCATION_WIDGET_ROOT . 'debug.log', gmdate('Y-m-d H:i:s') . "\t" . $message . "\n", FILE_APPEND);
	}

	public function plugin_enqueues($hook)
	{
		if ($hook == 'plugins.php') {
			// Add in required libraries for feedback modal
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_style('wp-jquery-ui-dialog');

			wp_enqueue_script('ip2location_widget_admin_script', plugins_url('/assets/js/feedback.js', __FILE__), ['jquery'], null, true);
		}
	}

	public function admin_footer_text($footer_text)
	{
		$plugin_name = substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.'));
		$current_screen = get_current_screen();

		if (($current_screen && strpos($current_screen->id, $plugin_name) !== false)) {
			$footer_text .= sprintf(
				__('Enjoyed %1$s? Please leave us a %2$s rating. A huge thanks in advance!', $plugin_name),
				'<strong>' . __('IP2Location Widget', $plugin_name) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/' . $plugin_name . '/reviews/?filter=5/#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}

		if ($current_screen->id == 'plugins') {
			return $footer_text . '
			<div id="ip2location-widget-feedback-modal" class="hidden" style="max-width:800px">
				<span id="ip2location-widget-feedback-response"></span>
				<p>
					<strong>Would you mind sharing with us the reason to deactivate the plugin?</strong>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-widget-feedback" value="1"> I no longer need the plugin
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-widget-feedback" value="2"> I couldn\'t get the plugin to work
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-widget-feedback" value="3"> The plugin doesn\'t meet my requirements
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="ip2location-widget-feedback" value="4"> Other concerns
						<br><br>
						<textarea id="ip2location-widget-feedback-other" style="display:none;width:100%"></textarea>
					</label>
				</p>
				<p>
					<div style="float:left">
						<input type="button" id="ip2location-widget-submit-feedback-button" class="button button-danger" value="Submit & Deactivate" />
					</div>
					<div style="float:right">
						<a href="#">Skip & Deactivate</a>
					</div>
				</p>
			</div>';
		}

		return $footer_text;
	}

	public function submit_feedback()
	{
		$feedback = (isset($_POST['feedback'])) ? sanitize_text_field($_POST['feedback']) : '';
		$others = (isset($_POST['others'])) ? sanitize_text_field($_POST['others']) : '';

		$options = [
			1 => 'I no longer need the plugin',
			2 => 'I couldn\'t get the plugin to work',
			3 => 'The plugin doesn\'t meet my requirements',
			4 => 'Other concerns' . (($others) ? (' - ' . $others) : ''),
		];

		if (isset($options[$feedback])) {
			if (!class_exists('WP_Http')) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}

			$request = new WP_Http();
			$response = $request->request('https://www.ip2location.com/wp-plugin-feedback?' . http_build_query([
				'name'    => 'ip2location-widget',
				'message' => $options[$feedback],
			]), ['timeout' => 5]);
		}
	}
}

$ip2location_widget = new IP2LocationWidget();
$ip2location_widget->init();

register_activation_hook(__FILE__, [$ip2location_widget, 'set_defaults']);
?>
