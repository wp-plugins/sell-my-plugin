<?php
/*
Plugin Name: 	Sell My Plugin Free
Plugin URI: 	http://redmine.landry.me/projects/sell-my-plugin
Description:	This plugin allows you to host your own plugins on your wordpress installation.
Author: 	Rob Landry
Version:	0.9.3
Author URI: 	http://www.landry.me/
*/
/*
Copyright (c) 2012 by Rob Landry

License: 	GPLv2
*/
#------------------------------------------------------------------------------
# Define Some VARS
# Since: 1.0
#------------------------------------------------------------------------------
	global $wpdb;
	$secret_dir = get_option('smp_secret_dir');

	if (empty($secret_dir)) {
		$secret_dir = gen_secret_dir();
		update_option('smp_secret_dir', $secret_dir);
	}

	if (!defined('SMP_QUERY_VAR'))		define('SMP_QUERY_VAR','plugin');
	if (!defined('SMP_SHORTCODE_QUERY_VAR'))define('SMP_SHORTCODE_QUERY_VAR','show_plugin');
	if (!defined('SMP_ACTION_QUERY_VAR'))	define('SMP_ACTION_QUERY_VAR','smp_action');
	if (!defined('SMP_VERSION_QUERY_VAR'))	define('SMP_VERSION_QUERY_VAR','smp_version');
	if (!defined('SMP_SCREENSHOT_QUERY_VAR'))define('SMP_SCREENSHOT_QUERY_VAR','screenshot');
	if (!defined('SMP_REDIRECT_DIR'))	define('SMP_REDIRECT_DIR','extend/plugins/');
	if (!defined('SMP_SECTION_QUERY_VAR'))	define('SMP_SECTION_QUERY_VAR','section');
	if (!defined('SMP'))			define('SMP', plugin_basename(__FILE__));
	if (!defined('SMP_URL'))		define('SMP_URL', plugin_dir_url( __file__ ));
	if (!defined('SMP_DIR'))		define('SMP_DIR', plugin_dir_path( __file__ ));
	if (!defined('SMP_UPLOAD_DIR'))		define('SMP_UPLOAD_DIR', WP_CONTENT_DIR.'/uploads/'.get_option('smp_secret_dir').'/');
	if (!defined('SMP_UPLOAD_URL'))		define('SMP_UPLOAD_URL', WP_CONTENT_DIR.'/uploads/'.get_option('smp_secret_dir').'/');
	if (!defined('SMP_PLUGINS_TBL'))	define('SMP_PLUGINS_TBL', $wpdb->prefix . 'smp_plugins');
	if (!defined('SMP_EP_URL'))		define('SMP_EP_URL', get_bloginfo('wpurl').'/extend/plugins/');
	if (!defined('SMP_ADMIN'))		define('SMP_ADMIN', admin_url('admin.php?page=sell-my-plugin'));
	global $smp_db_version;
	$smp_db_version = "0.9.3";
# End Define Vars


#------------------------------------------------------------------------------
# Plugin Init
# Since: 1.0
# A function to inititalize the plugin
#------------------------------------------------------------------------------
add_action('init','smp_init');
function smp_init(){
	global $smp_db_version;
	load_plugin_textdomain('sell-my-plugin', false, basename( dirname( __FILE__ ) ) . '/languages' );

	$installed_ver = get_option( "smp_db_version" );
	add_action( 'wp_enqueue_scripts', 'wp_header' );

	if( empty($installed_ver) ) {
		make_tables(); 
		update_option("smp_db_version", $smp_db_version);
	}

	if (!empty($installed_ver) && ($smp_db_version > $installed_ver)) {
		make_tables();
		update_option("smp_db_version", $smp_db_version);
	}

	global $smp_plugin;
	require_once('inc/class-plugin.php');
	$smp_plugin = new smp_plugin();

	require_once('inc/admin.php');
} # End Init


#------------------------------------------------------------------------------
# Activation Hook
# Since: 1.0
# A function to Register the Activation Hook
#------------------------------------------------------------------------------
function smp_activate() {
	global $wpdb;
	global $smp_db_version;
	$installed_ver = get_option( "smp_db_version" );
	if( $installed_ver != $smp_db_version ) {
		make_tables();
	}

	$upload_index = WP_CONTENT_DIR.'/uploads/index.php';
	if (!is_file($upload_index)) make_index($upload_index);
	if (!is_dir(SMP_UPLOAD_DIR)) mkdir(SMP_UPLOAD_DIR);
	$upload_index = SMP_UPLOAD_DIR.'index.php';
	if (!is_file($upload_index)) make_index($upload_index);
}
register_activation_hook(__FILE__, 'smp_activate');
# End Activation Hook


#------------------------------------------------------------------------------
# Deactivation Hook
# Since: 1.0
# A function to Register the Deactivation Hook
#------------------------------------------------------------------------------
function smp_deactivate() {
	if (get_option('smp_del_on_deactivate') == 'TRUE') {
		smp_uninstall();
	}
}
register_deactivation_hook(__FILE__, 'smp_deactivate');
# End Deactivation Hook


#------------------------------------------------------------------------------
# Uninstall Hook
# Since: 1.0
# A function to Register the Uninstall Hook
#------------------------------------------------------------------------------
function smp_uninstall() {
        global $wpdb;
	$rows = $wpdb->get_results("SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE 'smp_%'");
	foreach ($rows as $row) delete_option($row->option_name);
	$wpdb->query("DROP TABLE IF EXISTS".SMP_PLUGINS_TBL);
}
register_uninstall_hook(__FILE__, 'smp_uninstall');
# End Uninstall Hook


#------------------------------------------------------------------------------
# Make Tables
# Since: 1.0
# A function to make the tables
#------------------------------------------------------------------------------
function make_tables(){
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$sql2 = "CREATE TABLE ".SMP_PLUGINS_TBL." (
		plugin_id int(6) unsigned AUTO_INCREMENT NOT NULL,
		plugin_name varchar(255),
		plugin_slug varchar(255),
		plugin_versions varchar(255),
		plugin_price varchar(25),
		plugin_downloads int(10) unsigned default 0,
		plugin_ratings int(10) unsigned default 0,
		plugin_rating varchar(255),
		plugin_modified_timestamp timestamp default NOW(),
		UNIQUE KEY id (plugin_id)		  
	);";
	dbDelta($sql2);
}
#------------------------------------------------------------------------------
# Settings Link
# Since: 1.0
# A function Add settings link on plugin page
#------------------------------------------------------------------------------
add_filter("plugin_action_links_".SMP, 'smp_settings_link' );
function smp_settings_link($links) { 
	$settings_link = '<a href="admin.php?page=sell-my-plugin">Settings</a>'; 
	array_unshift($links, $settings_link); 
	return $links; 
}
# End Settings Link


#------------------------------------------------------------------------------
# Troubleshoot Activation Error
# Since: 1.0
# A function to assist with activation errors
#------------------------------------------------------------------------------
//add_action('activated_plugin','save_error');
function save_error(){
	//update_option('plugin_error',  ob_get_contents());
	//echo get_option('plugin_error') ."<br>";
	//update_option('plugin_error', '');
	file_put_contents(SMP_DIR.'error_activation.html', ob_get_contents());
}
# End Troubleshoot Activation Error


#------------------------------------------------------------------------------
# Template Redirects
# Since: 1.0
# A function to provide redirects
#------------------------------------------------------------------------------
function smp_template_redirect(){
	//require_once('inc/class-plugin.php');
	$uri = $_SERVER['REQUEST_URI'];
	if (strpos($uri,'?') && strpos($uri, 'section=') && (strpos($uri, '/section/') || strpos($uri, '/details'))) {
		list($uri,$query) = explode('?', $uri);
		$test_uri = $uri;
		if (strrpos($test_uri,'/')+1 == strlen($test_uri)) $test_uri = substr_replace($test_uri ,"",-1);
		$uri_pieces = explode('/',$test_uri);
		$no_uri_pieces = count($uri_pieces);
		if ($uri_pieces[1] == 'extend') {
			$test_url = '/extend';
			if ($uri_pieces[2] == 'plugins') {
				$test_url .= '/plugins';
				$test_plugin = $uri_pieces[3];
				$test_url .= "/$test_plugin";
				if (($uri_pieces[4] != 'section') && ($uri_pieces[4] != 'details')) {
					$test_section = $_REQUEST['section'];
					$test_version = $uri_pieces[4];
					$test_url .= "/$test_version/section/$test_section/";
				} else {
					$test_section = $_REQUEST['section'];
					$test_url .= "/section/$test_section/";
				}
			}
		}
		$uri = str_replace('/extend/plugins/','',$uri);
		$plugin = substr($uri,0,stripos($uri,'/'));
		$uri = str_replace($plugin,'',$uri);
		$uri = str_replace('section/','',$uri);
		$section = rtrim($uri,'/');
		$squery = explode('&',$query);
		$new_query = '';
		foreach($squery as $sub_query) {
			if (strpos($sub_query,'section') !== false) {
				$section = str_replace('section=','',$sub_query);
			} else {
				if($new_query == '') {$new_query = "?$sub_query";}
				else {$new_query .= "&$sub_query";}
			}
		}
		$url = $test_url.$new_query;
		wp_redirect($url);
		exit();
	}

	if ($smp_query_var = get_query_var(SMP_QUERY_VAR)) {

		global $wp_query;
	global $smp_plugin;
		//$smp_plugin = new smp_plugin();
		if (!$smp_plugin::plugin_exists($smp_query_var)) {
			$wp_query->is_404 = true;
			return;
		}

		$Plugin = $smp_plugin::get_plugin_by_slug($smp_query_var);
		$Versions = $smp_plugin::get_versions($smp_query_var);
		if ($version = get_query_var(SMP_VERSION_QUERY_VAR) ){
			if (empty($Versions[$version])){
				$wp_query->is_404 = true;
				return;
			}
		}
		else{
			if (get_query_var(SMP_ACTION_QUERY_VAR) == ''){
				if (apply_filters('smp_enable_redirect',true)){
					$smp_plugin::smp_spoof_page($Plugin,key($Versions));
				}
				else{
					$wp_query->is_404 = true;
				}
				return;
			}
		}
		$PluginFile = maybe_unserialize($Plugin->plugin_versions);
		$Plugin->plugin_data = $smp_plugin::get_plugin_details(SMP_UPLOAD_DIR.$PluginFile[key($Versions)]['filename']);
		switch(get_query_var(SMP_ACTION_QUERY_VAR)){
		case 'details':
			echo "<link rel='stylesheet' href='".SMP_URL."css/smp_styles.css' ";
			echo "type='text/css' media='all'>";
			$_REQUEST['section'] = get_query_var(SMP_SECTION_QUERY_VAR); 
			$return = $smp_plugin::smp_install_plugin_information($smp_query_var);
			echo $return;
			exit();
		case 'screenshot':
			$PluginZip = SMP_UPLOAD_DIR.$Versions[$version]['filename'];
			$smp_plugin::send_screenshot_from_zip($PluginZip,get_query_var(SMP_SCREENSHOT_QUERY_VAR));
			break;
		default:

			if (!apply_filters('allow_smp_download',true,$Plugin,$version)){
				wp_die('You do not have permission to download this plugin');
				return;
			}
			$details = $Versions[$version];
			if (!file_exists(SMP_UPLOAD_DIR.$details['filename'])){
				return;
			}
			$downloads = $smp_plugin::get_parameter($smp_query_var, 'plugin_downloads');
			$smp_plugin::set_parameter($smp_query_var, 'plugin_downloads', intval($downloads)+1);
			header("Vary: User-Agent");
			header("Content-disposition: attachment; filename=\"".$details['filename']."\"");
			header("Content-Type: application/zip");
			readfile(SMP_UPLOAD_DIR.$details['filename']);
			exit();
		}
	} elseif(array_key_exists('REDIRECT_URL',$_SERVER) && $_SERVER['REDIRECT_URL'] == '/extend/plugins/') {
		add_action( 'wp_enqueue_scripts', 'wp_header' );
		require_once('inc/class-spoof-page.php');
		require_once('inc/class-plugin.php');
		$smp_plugin = new smp_plugin();
		$Spoof = new spoof_page;
		$Spoof->page_slug = 'plugins';
		$Spoof->page_title = 'Plugins';
		$Spoof->content = $smp_plugin::list_plugins();
		$Spoof->post_type = 'Plugins';
		$Spoof->force_injection = true;
		if (!defined('SMP_SPOOFED_PLUGIN_PAGE')) define('SMP_SPOOFED_PLUGIN_PAGE',true);
	}
}
add_action( 'pre_get_posts', 'smp_template_redirect' );
# End Template Redirects


#------------------------------------------------------------------------------
# Custom Rewrites
# Since: 1.0
# A function to add custom rewrites
#------------------------------------------------------------------------------
function smp_rewrite_rules($rules){
	# yourdomain.com/extend/plugins/<your-plugin>/
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]'; // the plugin page
	# yourdomain.com/extend/plugins/<your-plugin>/details/
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/details/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_ACTION_QUERY_VAR.'=details'; // a request for the details of the plugin
	# yourdomain.com/extend/plugins/<your-plugin>/section/<the-section>
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/section/([^/]+)/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_ACTION_QUERY_VAR.'=details&'.SMP_SECTION_QUERY_VAR.'=$matches[2]'; // a particular section of the details page
	# yourdomain.com/extend/plugins/<your-plugin>/<your-version>/section/<the-section>
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/([^/]+)/section/([^/]+)/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_ACTION_QUERY_VAR.'=details&'.SMP_VERSION_QUERY_VAR.'=$matches[2]&'.SMP_SECTION_QUERY_VAR.'=$matches[3]'; // a particular section of the details page
	# yourdomain.com/extend/plugins/<your-plugin>/<version>
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/([^/]+)/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_VERSION_QUERY_VAR.'=$matches[2]'; // a plugin + version = direct download
	# yourdomain.com/extend/plugins/<your-plugin>/<version>/details/
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/([^/]+)/details/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_VERSION_QUERY_VAR.'=$matches[2]&'.SMP_ACTION_QUERY_VAR.'=details'; // details on a specific version
	# yourdomain.com/extend/plugins/<your-plugin>/<version>/section/
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/([^/]+)/section/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_VERSION_QUERY_VAR.'=$matches[2]&'.SMP_ACTION_QUERY_VAR.'=details&'.SMP_SECTION_QUERY_VAR.'=$matches[3]'; // section of details page for a particular version
	$smp_rules[SMP_REDIRECT_DIR.'([^/]+)/([^/]+)/screenshot/([^/]+)/?$'] = 'index.php?'.SMP_QUERY_VAR.'=$matches[1]&'.SMP_VERSION_QUERY_VAR.'=$matches[2]&'.SMP_ACTION_QUERY_VAR.'=screenshot&'.SMP_SCREENSHOT_QUERY_VAR.'=$matches[3]'; // a particular screenshot from a particular version
	$smp_rules = apply_filters('smp_rewrite_rules',$smp_rules);
	$rules = $smp_rules + $rules;
	return $rules;
}
add_filter('option_rewrite_rules','smp_rewrite_rules');
# End Rewrites


#------------------------------------------------------------------------------
# Enqueue Styles
# Since: 1.0
# A function to enqueue styles
#------------------------------------------------------------------------------
function wp_header() {
	wp_enqueue_style('smp_stylesheet',SMP_URL.'css/smp_styles.css');
} # End Enqueue Styles


#------------------------------------------------------------------------------
# Query Vars
# Since: 1.0
# A function to add the query vars
#------------------------------------------------------------------------------
function smp_query_vars($query_vars){
	$query_vars[] = SMP_QUERY_VAR;
	$query_vars[] = SMP_VERSION_QUERY_VAR;
	$query_vars[] = SMP_ACTION_QUERY_VAR;
	$query_vars[] = SMP_SCREENSHOT_QUERY_VAR;
	$query_vars[] = SMP_SECTION_QUERY_VAR;
	return $query_vars;
}
add_filter('query_vars','smp_query_vars');
# End Query Vars


#------------------------------------------------------------------------------
# Shortcodes
# Since: 1.0
# A function to add Shortcodes
#------------------------------------------------------------------------------
add_shortcode('sell-my-plugin','smp_shortcodes');
function smp_shortcodes($atts){
	global $smp_plugin;
	extract(shortcode_atts(array(
		'id' => '',
		'slug' => '',
		'version' => '',
		'history' => false,
		'details' => false,
		'format' => '',
		'heading' => 'My Plugins'
	), $atts));
	$return = '';
	if ($id != '') {
		$return .= $smp_plugin::list_plugins($id);
	} elseif ($slug != '') {
			$return .= $smp_plugin::list_plugins($slug);
	} else {
		$return .= $smp_plugin::list_plugins();
	}
	return $return;
} # End Shortcodes


#------------------------------------------------------------------------------
# Secret Dir
# Since: 1.0
# A function to create a secret directory to prevent hotlinking files.
#------------------------------------------------------------------------------
function gen_secret_dir() {
	$password = md5(uniqid('', true));
	$salt = md5(uniqid('', true));
	$iterations = 10;
	$hash = crypt($password,$salt);
	for ($i = 0; $i < $iterations; ++$i) {
		$hash = crypt($hash . $password,$salt);
	}
	return $hash;
} # End Secret Dir


#------------------------------------------------------------------------------
# Make Index
# Since: 1.0
# A function to create an index file to prevent viewing files.
#------------------------------------------------------------------------------
function make_index($upload_index) {
	$data = '<?php
/*
	Plugin Index for Sell My Plugin

	Copyright (c) 2012 by Rob Landry
*/
?>
<div align=center>
*******************************************************************************
Sell My Plugin Index
*******************************************************************************
</div>
<font color=red><strong>Directory Browsing Disabled!!</strong></font>
';

	$handle = fopen($upload_index, 'w') or die('Cannot open file:  '.$upload_index);
	fwrite($handle, $data);
}
?>
