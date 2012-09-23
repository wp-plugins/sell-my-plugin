<?php
/*
	Admin for Sell My Plugin

	Copyright (c) 2012 by Rob Landry
*/

	add_action( 'admin_init', 'smp_admin_init' );
	add_action( 'admin_menu', 'smp_menu' );


	#----------------------------------------------------------------------
	# Admin Init
	# Since: 1.0
	# A function to initialize the admin page
	# Used in: 
	#----------------------------------------------------------------------
	function smp_admin_init() {
		require_once('class-plugins-list-table.php');
		global $plugins_table;
	} # End Admin Init


	#----------------------------------------------------------------------
	# Menu
	# Since: 1.0
	# A function to initialize the admin menu
	# Used in: 
	#----------------------------------------------------------------------
	function smp_menu() {
		global $smp_plugins;
		$menu = add_menu_page( 'Sell My Plugin', 'Sell My Plugin', 'manage_options', 'sell-my-plugin', 'smp_options' );
		$settings = add_submenu_page( 'sell-my-plugin', __('Settings','sell-my-plugin'), __('Settings','sell-my-plugin'), 'manage_options', 'sell-my-plugin', 'smp_options');
		$smp_plugins = add_submenu_page( 'sell-my-plugin', __('Plugins','sell-my-plugin'), __('Plugins','sell-my-plugin'), 'manage_options', 'sell-my-plugin-plugins', 'smp_plugins');
		$help = add_submenu_page( 'sell-my-plugin', __('Help','sell-my-plugin'), __('Help','sell-my-plugin'), 'manage_options', 'sell-my-plugin-help', 'smp_help');

		add_action("load-$smp_plugins", "smp_plugins_screen_options");
		add_action('admin_print_styles-' . $settings, 'smp_enqueue_plugin_styles');
		add_action('admin_print_styles-' . $smp_plugins, 'smp_enqueue_plugin_styles');
		add_action('admin_print_scripts-' . $settings, 'smp_enqueue_plugin_scripts');
		add_action('admin_print_scripts-' . $smp_plugins, 'smp_enqueue_plugin_scripts');
	} # End Menu


	#----------------------------------------------------------------------
	# Enqueue Scripts
	# Since: 1.0
	# A function to enqueue the scripts
	# Used in: 
	#----------------------------------------------------------------------
	function smp_enqueue_plugin_scripts() {
		wp_enqueue_script('thickbox');
	} 

	function smp_enqueue_settings_scripts() {
		add_action( 'wp_enqueue_scripts', 'smp_header' );
	} # End Enqueue Scripts


	#----------------------------------------------------------------------
	# Enqueue Styles
	# Since: 1.0
	# A function to enqueue the styles
	# Used in: 
	#----------------------------------------------------------------------
	function smp_enqueue_plugin_styles() {
		wp_enqueue_style('thickbox');
	}

	function smp_enqueue_settings_styles() { 
		$url = SMP_URL.'css/stars.png?19'; ?>
		<style type="text/css">
			div.plugin-block div.star-holder {float: left;margin: -3px 0 0 .5ex;}
			div.star-holder {position: relative;height: 17px;width: 92px !important;background: url('<?php echo $url; ?>') repeat-x bottom left !important;}
			div.star-holder .star-rating {background: url('<?php echo $url; ?>') repeat-x top left !important;height: 17px;float: left;text-indent: 100%;overflow: hidden;white-space: nowrap;}
			div.star-holder.rate .star-rate {position: absolute;}
			div.star-holder.rate:hover .star-rating {display: none;}
			div.star-holder .star-rate a {background: none;height: 17px;width: 19px;float: right;}
			div.star-holder .star-rate a:first-child {width: 16px;}
			div.star-holder .star-rate a:hover ~ a,div.star-holder .star-rate a:hover {background: url('<?php echo $url; ?>') no-repeat top left !important;}
		</style><?php
	} # End Enqueue Styles


	#----------------------------------------------------------------------
	# Plugins Screen Options
	# Since: 1.0
	# A function to enqueue the screen options
	# Used in: 
	#----------------------------------------------------------------------
	add_filter('set-screen-option', 'smp_plugins_set_option', 10, 3);
	function smp_plugins_set_option($status, $option, $value) {
		return $value;
	} # End Plugins Set Screen Options


	#----------------------------------------------------------------------
	# Plugins Screen Options
	# Since: 1.0
	# A function to enqueue the screen options
	# Used in: 
	#----------------------------------------------------------------------
	function smp_plugins_screen_options () {
		require_once('class-plugins-list-table.php');
		global $smp_plugins;
		global $plugins_table;
		$screen = get_current_screen();
		// get out of here if we are not on our settings page
		if(!is_object($screen) || $screen->id != $smp_plugins)
			return;
	 	$option = 'per_page';
		$args = array(
			'label' => 'Plugins per page',
			'default' => 20,
			'option' => 'plugins_per_page'
		);
		add_screen_option( 'per_page', $args );
		$plugins_table = new Plugins_List_Table();
	} # End Plugins Screen Options


	#----------------------------------------------------------------------
	# Plugins Page
	# Since: 1.0
	# A function to display the Plugins Page
	# Used in: 
	#----------------------------------------------------------------------
	function smp_plugins() {
		global $smp_plugin;
		if (!empty($_REQUEST['action'])) { $get_action = $_REQUEST['action']; } else { $get_action = false; }
		if (!empty($_REQUEST['smp_nonce'])) { $get_nonce = $_REQUEST['smp_nonce'];  } else { $get_nonce = false; }
		if (!empty($_GET['price'])) { $get_price = $_GET['price']; } else { $get_price = false; }
		if (!empty($_GET['plugin']))  { $get_plugin = $_GET['plugin'];  } else { $get_plugin = false; }
		if (!empty($_GET['version']))  { $get_version = $_GET['version']; } else { $get_version = false; }
		if ($get_action) {
			switch ($get_action) {
				case 'upload':
					if (wp_verify_nonce($get_nonce, 'upload') ) {
						$result = fileupload_process();
						if ($result == 'success') {
							echo '<div id="message" class="updated fade">';
							echo "<p>". __('File Upload Complete.', 'sell-my-plugin') ."</p></div>"; }
						else {
							echo '<div id="error" class="error fade">';
							if (is_array($result)) { 
								echo "<p>";
								print_r($result);
								echo "</p></div>"; }
							else echo "<p>$result</p></div>"; }
					}
					break;
				case 'saveprice':
					if (wp_verify_nonce($get_nonce, 'saveprice') ) {
						if ($get_price && $get_plugin) {
							echo '<div id="message" class="updated fade">';
							echo "<p>". __('Plugin Price Not Set. Please purchase <a href=http://www.landry.me/extend/plugins/sell-my-plugin/ target=_blank>Pro</a> if you want to charge for your plugins.', 'sell-my-plugin') ."</p></div>";
						}
					}
					break;
				case 'delete':
					if (wp_verify_nonce($_REQUEST['_wpnonce'], 'delete') ) {
						if ($get_plugin && $get_version) {
							$msg = $smp_plugin::del_plugin_version($get_plugin,$get_version);
							if ($msg) {
								echo '<div id="message" class="updated fade">';
								echo "<p>". __('Plugin Version Deleted.', 'sell-my-plugin') ."</p></div>"; 
							} else {
								echo '<div id="error" class="error fade">';
								echo "<p>$msg</p></div>"; 
							}
						} elseif ($get_plugin && !$get_version) {
							$msg = $smp_plugin::del_plugin($get_plugin);
							if ($msg) {
								echo '<div id="message" class="updated fade">';
								echo "<p>". __('Plugin Deleted.', 'sell-my-plugin') ."</p></div>"; 
							} else {
								echo '<div id="error" class="error fade">';
								echo "<p>$msg</p></div>"; 
							}
						} else {
							echo '<div id="error" class="error fade">';
							echo "<p>". __('No Plugin Provided.', 'sell-my-plugin') ."</p></div>"; }
						}
					else {echo 'Security Check';}
					break;
				default:
					echo '<div id="message" class="updated fade">';
					echo "<p>". __('Sell my plugin is available to <a href=http://www.landry.me/extend/plugins/sell-my-plugin/ target=_blank>purchase</a> and unlock all of the advanced features.', 'sell-my-plugin') ."</p></div>";
					break;
			}
		}
		global $plugins_table;
		$plugins_table = new Plugins_List_Table();
		$plugins_table->prepare_items(); ?>
		<div class="wrap">
			<div id="icon-plugins" class="icon32"><br /></div>
			<h2>Sell My Plugin Plugins</h2>
			<?php if (get_option('smp_api_sandbox') == 'TRUE'): ?>
				<div id="message" class="error fade">
				<p><?php _e('Displaying Sandbox Downloads!', 'sell-my-plugin'); ?></p></div>
			<?php endif; ?><br />
			<form method="post">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $plugins_table->search_box('search', 'search_id'); ?>
			</form>
			<table><tr>
				<td>
				<form name="uploadfile" id="uploadfile_form" method="POST" enctype="multipart/form-data" action="<?php echo admin_url('admin.php').'?page='.$_REQUEST['page'].'&action=upload'; ?>" accept-charset="utf-8" >
				<input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" />
				<?php wp_nonce_field('upload','smp_nonce',false); ?>
				<input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Upload"  />
				</form>
				</td></tr>  
			</table>

			<form id="plugins-list" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $plugins_table->display() ?>
			</form>
		</div><?php
	} # End Plugins Page


	#----------------------------------------------------------------------
	# Options Page
	# Since: 1.0
	# A function to display the Options Page
	# Used in: 
	#----------------------------------------------------------------------
	function smp_options() {
		$pro_disabled = "disabled";
		$disabled_color = "color: #CCC;"; ?>
		<div class="wrap">
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2>Sell My Plugin Options</h2>
			<div id="message" class="updated fade">
			<p><?php _e('Sell my plugin is available to <a href=http://www.landry.me/extend/plugins/sell-my-plugin/ target=_blank>purchase</a> and unlock all of the advanced features.', 'sell-my-plugin'); ?></p></div><br />
		<div class="widget-liquid-left">
		<form name=form1 action='<?php echo SMP_ADMIN; ?>&action=config' method='post'>
		<div id="widgets-left">

		<table class=widefat cellspacing=5 width=700px>
			<thead><tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('General Settings','sell-my-plugin'); ?></th></tr></thead>
			<tr><td width='134px' style='<?php echo $disabled_color; ?>'><strong><?php _e('Rating System', 'sell-my-plugin'); ?></strong></td>
			<td width='184px'><select name='smp_allow_rating' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'>
			<?php
				$smp_rating_n = $smp_rating_b = $smp_rating_s = $smp_rating_p = '';
				if (get_option('smp_allow_rating') == 'PLUGIN') $smp_rating_p = 'selected';
				if (get_option('smp_allow_rating') == 'SITE') $smp_rating_s = 'selected';
				if (get_option('smp_allow_rating') == 'BOTH') $smp_rating_b = 'selected';
				if (get_option('smp_allow_rating') == 'NONE') $smp_rating_n = 'selected';
			?>
			<option value='NONE' <?php echo $smp_rating_n; ?>><?php _e('None', 'sell-my-plugin'); ?></option>
			<option value='PLUGIN' <?php echo $smp_rating_p; ?>><?php _e('Plugin', 'sell-my-plugin'); ?></option>
			<option value='SITE' <?php echo $smp_rating_s; ?>><?php _e('Site', 'sell-my-plugin'); ?></option>
			<option value='BOTH' <?php echo $smp_rating_b; ?>><?php _e('Both', 'sell-my-plugin'); ?></option>
			</select></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This setting enables and disables the rating system. The options are: <br><strong>None</strong> which disables ratings, <br><strong>Plugin</strong> which only allows ratings from the remote plugin admin page, <br><strong>Site</strong> which only allows ratings from the plugin discription located on this site, and <br><strong>Both</strong> which allows ratings from both Site and Plugin.', 'sell-my-plugin'); ?></td></tr>

			<!-- Content Width -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Content Width', 'sell-my-plugin'); ?></strong></td>
			<td style='<?php echo $disabled_color; ?>'><input type='text' name='smp_content_width' value='<?php echo get_option('smp_content_width'); ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This sets the content width. Tested with 2011 theme.', 'sell-my-plugin'); ?></td></tr>


			<tfoot><tr><th colspan=3></th></tr></tfoot>
		</table><br>


		<table class=widefat cellspacing=5 width=700px>
			<thead><tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('PayPal Settings', 'sell-my-plugin'); ?>  -  <a href=https://developer.paypal.com/devscr?cmd=_signup-run title='Sandbox Signup' target=_blank>Sandbox Signup</a>  -  <a href=https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_testing_sandbox_get_started title='Sandbox Getting Started' target=_blank>Sandbox Getting Started</a></th></tr></thead>

			<!-- Clean on Deactivation -->
			<tr><td width='134px' style='<?php echo $disabled_color; ?>'><strong><?php _e('Clean on Deactivation', 'sell-my-plugin'); ?></strong></td>
			<td width='184px'><select name='smp_del_on_deactivate' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'>
			<?php	if (get_option('smp_del_on_deactivate') == 'FALSE') {
		                $clean_true = "";
		                $clean_false= "selected";
		        } else {
		                $clean_true = "selected";
		                $clean_false= "";
		        } ?>
			<option value='TRUE' <?php echo $clean_true; ?>><?php _e('TRUE', 'sell-my-plugin'); ?></option>
			<option value='FALSE' <?php echo $clean_false; ?>><?php _e('FALSE', 'sell-my-plugin'); ?></option>
			</select></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This will remove the database and all options once the plugin is deactivated. This is the default behavior for uninstallation.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal API Username -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('PayPal API Username', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_api_username' value='<?php echo get_option('smp_api_username'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your PayPal API Username.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal API Password -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('PayPal API Password', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_api_password' value='<?php echo get_option('smp_api_password'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your PayPal API password.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal API Signature -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('PayPal API Signature', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_api_signature' value='<?php echo get_option('smp_api_signature'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your PayPal API signature.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal Sandbox -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Use PayPal Sandbox', 'sell-my-plugin'); ?></strong></td>
			<td><select name='smp_api_sandbox' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'>
			<?php	if (get_option('smp_api_sandbox') == 'FALSE') {
		                $sandbox_true = "";
		                $sandbox_false= "selected";
		        } else {
		                $sandbox_true = "selected";
		                $sandbox_false= "";
		        } ?>
			<option value='TRUE' <?php echo $sandbox_true; ?>><?php _e('TRUE', 'sell-my-plugin'); ?></option>
			<option value='FALSE' <?php echo $sandbox_false; ?>><?php _e('FALSE', 'sell-my-plugin'); ?></option>
			</select></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This allows you to test purchases prior to going live. You MUST change this along with api info in order to accept payments.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal Use Proxy -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Use Proxy', 'sell-my-plugin'); ?></strong></td>
			<td><select name='smp_api_proxy_use' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'>
			<?php	if (get_option('smp_api_proxy_use') == 'FALSE') {
		                $use_proxy_true = "";
		                $use_proxy_false= "selected";
		        } else {
		                $use_proxy_true = "selected";
		                $use_proxy_false= "";
		        } ?>
			<option value='TRUE' <?php echo $use_proxy_true; ?>><?php _e('TRUE', 'sell-my-plugin'); ?></option>
			<option value='FALSE' <?php echo $use_proxy_false; ?>><?php _e('FALSE', 'sell-my-plugin'); ?></option>
			</select></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is where you chose to use a proxy or not. Most users should select FALSE.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal Proxy Host -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('PayPal Proxy Host', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_api_proxy_host' value='<?php echo get_option('smp_api_proxy_host'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your proxy host. Most users should not worry about this.', 'sell-my-plugin'); ?></td></tr>

			<!-- PayPal Proxy Port -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('PayPal Proxy Port', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_api_proxy_port' value='<?php echo get_option('smp_api_proxy_port'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your proxy port. Most users should not worry about this.', 'sell-my-plugin'); ?></td></tr>
			<tfoot><tr><th colspan=3></th></tr></tfoot>
		</table><br>

		<table class=widefat cellspacing=5 width=700px>
			<thead><tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Email Response','sell-my-plugin'); ?></th></tr></thead>
			<tr><td colspan=3 style='<?php echo $disabled_color; ?>'>
			<?php 	$out = __('Shortcodes to use: ','sell-my-plugin'); 
				$out .= "<span title='Plugin Name'>[plugin] </span>";
				$out .= "<span title='Plugin Slug'>[slug] </span>";
				$out .= "<span title='Payers Name'>[name] </span>";
				$out .= "<span title='Payers ID'>[pid] </span>";
				$out .= "<span title='Transaction ID'>[tid] </span>";
				$out .= "<span title='Amount Paid'>[amount] </span>";
				$out .= "<span title='Purchase Time'>[time] </span>";
				echo $out;
			?>
			</td></tr>

			<!-- Name -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Email Name', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_email_name' value='<?php echo get_option('smp_email_name'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is the name you want purchasers to see.', 'sell-my-plugin'); ?></td></tr>

			<!-- Email Address -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Email Address', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_email_address' value='<?php echo get_option('smp_email_address'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is the email address you want purchasers to see.', 'sell-my-plugin'); ?></td></tr>

			<!-- Fake Email Address -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('YOUR Email Address', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_fake_email_address' value='<?php echo get_option('smp_fake_email_address'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is the email address ONLY for test email and emails when using sandbox. If left blank, when completing purchases in sandbox, the users email will be used.', 'sell-my-plugin'); ?></td></tr>

			<!-- Email Subject -->
			<tr><td style='<?php echo $disabled_color; ?>'><strong><?php _e('Email Subject', 'sell-my-plugin'); ?></strong></td>
			<td><input type='text' name='smp_email_subject' value='<?php echo get_option('smp_email_subject'); ?>' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'></td>
			<td style='<?php echo $disabled_color; ?>'><?php _e('This is your email subject. Default: [plugin] Purchase', 'sell-my-plugin'); ?></td></tr>

			<!-- Email Body -->
			<tr><td colspan=3 style='<?php echo $disabled_color; ?>'>
			<strong><?php _e('Email Body','sell-my-plugin'); ?></strong>

			</td></tr> <?php
			if (!empty($_REQUEST['reset_email']) && $_REQUEST['reset_email'] == 'true') {
				$content = 'This is my default email stuff.';
			} else {
				$content = get_option('smp_email_body');
			}
			$id = 'smp_email_body';
			$settings = array(
				'media_buttons' => false,
				'tinymce' => true,
				'teeny' => true
			); ?>
			<tr><td colspan=3 style='<?php echo $disabled_color; ?>'>
			<textarea name="smp_email_body" id="smp_email_body" rows="10" cols="93" <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'><?php echo get_option('smp_email_body'); ?></textarea>
			</td></tr>
			<tr><td colspan=3><button id=test_email name=test_email class='button-secondary' value='true' <?php echo $pro_disabled; ?>>Send Test Email</button></td></tr>
			<tfoot><tr><th colspan=3></th></tr></tfoot>
		</table><br>

		<table class=widefat cellspacing=5 width=700px>
			<thead><tr><th valign=top style='<?php echo $disabled_color; ?>'><?php _e('Plugins Page Heading','sell-my-plugin'); ?></th></tr></thead>
			<!-- Heading -->
			<tr><td style='<?php echo $disabled_color; ?>'>
			<label for="smp_plugins_heading"><strong><?php _e('Heading', 'sell-my-plugin'); ?></strong></label>
			<textarea name="smp_plugins_heading" id="smp_plugins_heading" rows="10" cols="93" <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'><?php echo get_option('smp_plugins_heading'); ?></textarea>
			<?php _e('This is the heading that displays on the plugins page.', 'sell-my-plugin'); ?></td></tr>
			<tfoot><tr><th></th></tr></tfoot>
		</table>

		<?php wp_nonce_field('smp_nonce','smp_nonce'); ?>
		<p class="submit"><input class='button-primary' type="submit" name="Submit" value="<?php _e('Update Options', 'sell-my-plugin'); ?>" <?php echo $pro_disabled; ?>/></p>
		</div></form></div>

<!-- End Left -->

<!-- Start Right -->
		<div class="widget-liquid-right">
		<div id="widgets-right">

		<table class=widefat cellspacing=5>
			<thead><tr><th valign=top colspan=3 ><?php _e('Purchase Pro', 'sell-my-plugin'); ?></th></tr></thead>
			<tr><td colspan=3 >Sell My Plugin is available to purchase and unlock all of the advanced features.<br>
<form action='http://www.landry.me/extend/plugins/sell-my-plugin/purchase/?action=checkout' METHOD='POST' target='blank'>
<input type='hidden' name='domain' value='<?php echo urlencode(get_site_url()); ?>'>
<input type='hidden' name='slug' value='sell-my-plugin-pro'>
<input type='hidden' name='return' value='<?php echo urlencode("http://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>'>
<div class='alignright'>
<input class='button-primary' type='submit' name='Submit' value='Buy Now' /></div></form></td></tr>
			<tfoot><tr><th></th></tr></tfoot>
		</table><br>

		<!-- RSS -->
		<table class=widefat cellspacing=5>
			<thead><tr><th valign=top >News</th></tr></thead>
			<?php 
			$rss = fetch_feed('http://redmine.landry.me/projects/sell-my-plugin/news.atom');
			$out = '';
			if (!is_wp_error( $rss ) ) {
				$maxitems = $rss->get_item_quantity(50);     
				$rss_items = $rss->get_items(0, $maxitems);  

				if ($maxitems == 0) {
					$out = "<tr><td>Nothing to see here.</td></tr>";     
				} else {     

					foreach ( $rss_items as $item ) {

						$title = $item->get_title();
						$content = $item->get_content();
						$description = $item->get_description();
						$author = $item->get_author();
						$author = $author->get_name();

						$out .= "<tr><td>";
						$out .= "<a target='_BLANK' href='". $item->get_permalink() ."'  title='Posted ". $item->get_date('j F Y | g:i a') ."'>";
				       		$out .= "$title</a> $description";
						$out .= "</td></tr>";
					} 
				}
			} else {$out = "<tr><td>Nothing to see here.</td></tr>";}
		echo $out; ?>
			<tfoot><tr><th></th></tr></tfoot>
		</table><br>

		<!-- Force Check for Update -->
		<table class=widefat cellspacing=5>
			<thead><tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Convenient Functions', 'sell-my-plugin'); ?></th></tr></thead>
			<tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Force Check for Update', 'sell-my-plugin'); ?></th></tr>
			<tr><td colspan=3 style='<?php echo $disabled_color; ?>'><button class='button-secondary' onclick="window.location='<?php echo SMP_ADMIN; ?>&action=checkupdate';" <?php echo $pro_disabled; ?>><?php _e('Check for Update', 'sell-my-plugin'); ?></button></td></tr>

		<!-- Generate Free Key -->			
			<tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Generate a Free Key', 'sell-my-plugin'); ?></th></tr>
			<tr><form id='gen_key' method='get'>
			<input type='hidden' name='page' value='<?php echo $_REQUEST['page']; ?>'>
			<input type='hidden' name='action' value='free'>
			<td><select name='plugin' <?php echo $pro_disabled; ?> style='<?php echo $disabled_color; ?>'>
			<?php 
				global $wpdb;
				$plugins = $wpdb->get_results( 
						"
						SELECT      plugin_name, plugin_slug
						FROM        ".SMP_PLUGINS_TBL."
						"
					); 
				foreach($plugins as $plugin) {
					echo "<option value='".$plugin->plugin_slug."'>".$plugin->plugin_name."</option>";
				}
			?>
			</select><td>
			<td><?php wp_nonce_field('gen_key','smp_nonce',false); ?>
			<input type='submit' class='button-secondary' value='Generate' <?php echo $pro_disabled; ?>></td>
			</form></tr>

		<!-- Save Key -->
			<tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Save Key for Updates', 'sell-my-plugin'); ?></th></tr>
			<tr><form id='savekey' method='post'>
			<input type='hidden' name='page' value='<?php echo $_REQUEST['page']; ?>'>
			<input type='hidden' name='action' value='savekey'>
			<td><input type='text' name='key' value='<?php echo get_option('smp_key');?>' style='<?php echo $disabled_color; ?>'><td>
			<td><?php wp_nonce_field('save_key','smp_nonce',false); ?>
			<input type='submit' class='button-secondary' value='Save' <?php echo $pro_disabled; ?>></td>
			</form></tr>

		<!-- Reset Sandbox Transactions -->
			<tr><th valign=top colspan=3 style='<?php echo $disabled_color; ?>'><?php _e('Reset Sandbox Transactions', 'sell-my-plugin'); ?></th></tr>
			<tr><form id='reset_sandbox_txn' method='post'>
			<input type='hidden' name='page' value='<?php echo $_REQUEST['page']; ?>'>
			<input type='hidden' name='action' value='reset_sbox_txn'>
			<td colspan=3 style='<?php echo $disabled_color; ?>'><?php wp_nonce_field('reset_sbox_txn','smp_nonce',false); ?>
			<input type='submit' class='button-secondary' value='Reset Sandbox Txns' <?php echo $pro_disabled; ?>></td>
			</form></tr>

			<tfoot><tr><th colspan=3></th></tr></tfoot>
		</table><br>

</div></div><!-- End Right -->


	</div><!-- End Wrap --><?php
	} # End Options Page


	#----------------------------------------------------------------------
	# Help Page
	# Since: 1.0
	# A function to display the Help Page
	# Used in: 
	#----------------------------------------------------------------------
	function smp_help() { 
		global $wpdb;
		require_once(SMP_DIR.'lib/parse-readme.php');
		$Automattic_Readme = new Automattic_Readme();
		$plugins = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".SMP_PLUGINS_TBL.""), OBJECT);
		if (!empty($plugins)) {
			$plugin = $plugins[0]; 
		} else {
			$plugin->plugin_name = 'My Plugin';
			$plugin->plugin_price = '0.00';
			$plugin->plugin_slug = 'my-plugin';
		}
?>

		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2>Sell My Plugin Help</h2>
			<div id="message" class="updated fade">
			<p><?php _e('Sell my plugin is available to <a href=http://www.landry.me/extend/plugins/sell-my-plugin/ target=_blank>purchase</a> and unlock all of the advanced features.', 'sell-my-plugin'); ?></p></div><br />
			<table class=widefat cellspacing=5 width=700px>
				<thead><tr><th valign=top colspan=3>General Settings</th></tr></thead>
				<!--  -->
				<tr><td><p>1. You must create a form that points to 
				<?php echo SMP_EP_URL.'PLUGINSLUG/purchase/?action=checkout'; ?>
				<br>An example would be:<br>
				<textarea rows=9 cols=125>
<table><tr><td><?php echo $plugin->plugin_name; ?> is available to purchase for $<?php echo $plugin->plugin_price; ?><br>
<form action='<?php echo SMP_EP_URL.$plugin->plugin_slug.'/purchase/?action=checkout'; ?>' METHOD='POST' target='blank'>
<input type='hidden' name='domain' value='< ?php echo urlencode(get_site_url()); ?>'>
<input type='hidden' name='slug' value='<?php echo $plugin->plugin_name; ?>'>
<input type='hidden' name='return' value='< ?php echo urlencode("http://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>'>
<div class='alignright'>
<input class='button-primary' type='submit' name='Submit' value='Buy Now' /></div></form>
</td></tr></table>
				</textarea>
				</p><p>Which would look like this:<br>
				<table><tr><td><?php echo $plugin->plugin_name; ?> is available to purchase for $<?php echo $plugin->plugin_price; ?><br>
				<form action='<?php echo SMP_EP_URL.$plugin->plugin_slug.'/purchase/?action=checkout'; ?>' METHOD='POST' target='blank'>
				<input type='hidden' name='domain' value='<?php echo urlencode(get_site_url()); ?>'>
				<input type='hidden' name='slug' value='<?php echo $plugin->plugin_name; ?>'>
				<input type='hidden' name='return' value='<?php echo urlencode("http://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>'>
				<div class='alignright'>
				<input class='button-primary' type='submit' name='Submit' value='Buy Now' /></div></form>
				</td></tr></table>
				</p></td></tr>

				<tfoot><tr><th colspan=3></th></tr></tfoot>
			</table><br>

			<table class=widefat cellspacing=5 width=700px>
				<thead><tr><th valign=top colspan=3>FAQ</th></tr></thead><?php
					$readme = SMP_DIR.'readme.txt';
					if (file_exists($readme)) {
						$plugin_info = $Automattic_Readme->parse_readme($readme);
						if ($plugin_info && isset($plugin_info['sections']['frequently_asked_questions'])) {
							echo "<tr><td>".$plugin_info['sections']['frequently_asked_questions']."</td></tr>";
						}
					}
				?><tfoot><tr><th colspan=3></th></tr></tfoot>
			</table><br>
		</div><?php

	} # End Help Page


	#----------------------------------------------------------------------
	# File Upload
	# Since: 1.0
	# A function to handle file uploads
	# Used in: 
	#----------------------------------------------------------------------
	function fileupload_process() { 
		global $smp_plugin;
		$msg = '';
		if ($_FILES['uploadfiles']['error'][0] != 4) {
			$uploadfiles = $_FILES['uploadfiles']; 
		} else {
			$uploadfiles = ''; 
			$msg = __('No File Selected!', 'sell-my-plugin');
		}
		if (is_array($uploadfiles)) {
			foreach ($uploadfiles['name'] as $key => $value) {
				# look only for uploded files
				if ($uploadfiles['error'][$key] == 0) {
					$filetmp = $uploadfiles['tmp_name'][$key];
					# clean filename and extract extension
					$filename = $uploadfiles['name'][$key];
					# get file info
					# @fixme: wp checks the file extension....
					$filetype = wp_check_filetype( basename( $filename ), null );
					$filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
					$filename = $filetitle . '.' . $filetype['ext'];
					# Check if the filename already exist in the directory and rename the
					# file if necessary
					$i = 0;
					while ( file_exists( SMP_UPLOAD_DIR . $filename ) ) {
						$filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
						$i++;
					}
					$filedest = SMP_UPLOAD_DIR . $filename;
					# Check write permissions
					if ( !is_writeable( SMP_UPLOAD_DIR ) ) {
						$msg = __(sprintf('Unable to write to directory %s. Is this directory writable by the server?',SMP_UPLOAD_DIR), 'sell-my-plugins');
						return $msg;
					}
					# Save temporary file to uploads dir
					if ( !@move_uploaded_file($filetmp, $filedest) ){
						$msg = __(sprintf('Error, the file %s could not moved to : %s',$filetmp,$filedest), 'sell-my-plugins');
						continue;
					}
					$plugin_data = $smp_plugin::get_plugin_details($filedest);
					if (!is_string($plugin_data)) {
						$new_filetitle = $plugin_data->slug . $plugin_data->version;
						$new_filename = $new_filetitle.'.'. $filetype['ext'];
						rename(SMP_UPLOAD_DIR.$filename, SMP_UPLOAD_DIR.$new_filename);
						$filename = $new_filename;
						require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
						global $wpdb;
						$plugin_name = $plugin_data->name;
						$plugin_slug = $plugin_data->slug;
						$plugin_versions[$plugin_data->version] = array( 'filename' => $filename, 'date' => date("Y-m-d H:i:s"));
						$plugin_data->link = SMP_UPLOAD_DIR . $new_filename;
						$plugin_data_arr[$plugin_data->version] = $plugin_data ;
						if ($smp_plugin::plugin_exists($plugin_slug)) {
							$existing_versions = maybe_unserialize($smp_plugin::get_parameter($plugin_slug, 'plugin_versions'));
							$existing_versions[$plugin_data->version] = array( 'filename' => $filename, 'date' => date("Y-m-d H:i:s"));
							//$existing_versions[$plugin_data['version']] = array( 'filename' => $filename, 'date' => date("Y-m-d H:i:s"));
							$smp_plugin::set_versions($plugin_slug,$existing_versions);
							$existing_data = maybe_unserialize($smp_plugin::get_parameter($plugin_slug, 'plugin_data'));
							//$existing_data[$plugin_data['version']] = $plugin_data;
							$existing_data[$plugin_data->version] = $plugin_data;
							//$smp_plugin::set_data($plugin_slug,$existing_data);
							$smp_plugin::set_parameter($plugin_slug, 'plugin_modified_timestamp', date("Y-m-d H:i:s"));
						} else {
							$data = array( 
								'plugin_name' => $plugin_name, 
								'plugin_slug' => $plugin_slug,
								'plugin_versions' => serialize($plugin_versions)//,
								//'plugin_data' => serialize($plugin_data_arr)
							);
							$wpdb->insert(SMP_PLUGINS_TBL, $data);
						}
					} else {
						$msg = "$plugin_data -> $filename";
						$file = SMP_UPLOAD_DIR . $filename;
						unlink($file);
					}
				}
			}
		} else { 
			$msg = __('No File Selected!', 'sell-my-plugins'); 
		}

		if ($msg == '') {
			return 'success'; 
		} else {
			return $msg;
		}
	} # End File Upload


?>
