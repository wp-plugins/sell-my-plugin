<?php
/*
	Support class Sell My Plugin

	Copyright (c) 2012 by Rob Landry
*/
/*****************************************************************************/
/*		Plugin Class
/*****************************************************************************/
if (!class_exists('smp_plugin')) {
class smp_plugin {


	#----------------------------------------------------------------------
	# Process Plugin Archive
	# Since: 1.0
	# A function to process the plugin archive
	# Used in: none
	#----------------------------------------------------------------------
	function processPluginArchive($PluginZip,$action){
	}


	#----------------------------------------------------------------------
	# Get Versions
	# Since: 1.0
	# A function to get the plugin versions
	# Used in: 
	#----------------------------------------------------------------------
	function get_versions($slug){
		$versions = maybe_unserialize(self::get_parameter($slug,'plugin_versions'));
		if (!is_array($versions)) $versions = array();
		return $versions;
	}


	#----------------------------------------------------------------------
	# Set Version
	# Since: 1.0
	# A function to sort and then write the versions to db
	# Used in: 
	#----------------------------------------------------------------------
	function set_versions($slug,$version_array){
		$keys = array_keys($version_array);
		natcasesort($keys);
		$sorted_keys = array_reverse($keys); // descending
		$_version_array = array();
		foreach ($sorted_keys as $key){
			$_version_array[$key] = $version_array[$key];
		}
		$version_array = $_version_array;
		self::set_parameter($slug,'plugin_versions',serialize($version_array));
	}


	#----------------------------------------------------------------------
	# Get Plugin by Slug
	# Since: 1.0
	# A function to get the plugin from database by slug
	# Used in: 
	#----------------------------------------------------------------------
	function get_plugin_by_slug($slug){
		global $wpdb;
		$plugin = $wpdb->get_row( "SELECT * FROM ".SMP_PLUGINS_TBL." WHERE plugin_slug = '$slug';" );
		return $plugin;
	}


	#----------------------------------------------------------------------
	# Set Data
	# Since: 1.0
	# A function to write the plugin data to db
	# Used in: none
	#----------------------------------------------------------------------
	function set_data($slug,$data_array){
		$keys = array_keys($data_array);
		natcasesort($keys);
		$sorted_keys = array_reverse($keys); // descending
		$_data_array = array();
		foreach ($sorted_keys as $key){
			$_data_array[$key] = $data_array[$key];
		}
		$data_array = $_data_array;
		self::set_parameter($slug,'plugin_data',serialize($data_array));
	}


	#----------------------------------------------------------------------
	# Set Parameter
	# Since: 1.0
	# A function to write the specific parameters to db
	# Used in: 
	#----------------------------------------------------------------------
	function set_parameter($slug, $parameter, $value) {
		global $wpdb;
		$wpdb->update( 
			SMP_PLUGINS_TBL, 
			array( $parameter => $value ), 
			array( 'plugin_slug' => $slug )
		);
	}


	#----------------------------------------------------------------------
	# Get Parameter
	# Since: 1.0
	# A function to get the specific parameters from db
	# Used in: 
	#----------------------------------------------------------------------
	function get_parameter($slug, $parameter) {
		global $wpdb;
		$return = $wpdb->get_results( $wpdb->prepare( 
		"
			SELECT $parameter 
			FROM ".SMP_PLUGINS_TBL."
			WHERE plugin_slug = %s
		", 
		$slug
		) );
		return $return[0]->$parameter;
	}


	#----------------------------------------------------------------------
	# Plugin Exists
	# Since: 1.0
	# A function to check if the plugin exists in db
	# Used in: 
	#----------------------------------------------------------------------
	function plugin_exists($slug) {
		global $wpdb;
		if( $wpdb->get_row( "SELECT plugin_slug FROM ".SMP_PLUGINS_TBL." WHERE plugin_slug = '$slug';" ) ) {
			return true;
		} else { return false; }
	}


	#----------------------------------------------------------------------
	# Delete the plugin version
	# Since: 1.0
	# A function to delete the specific version of the plugin from db and file
	# Used in: 
	#----------------------------------------------------------------------
	function del_plugin_version($slug, $version) {
		global $wpdb;
		$return = $wpdb->get_results( $wpdb->prepare( 
		"
			SELECT plugin_versions
			FROM ".SMP_PLUGINS_TBL."
			WHERE plugin_slug = %s
		", 
		$slug
		) );

		$plugin_versions = maybe_unserialize($return[0]->plugin_versions);

		if (!empty($plugin_versions[$version]) ) {
			$file = SMP_UPLOAD_DIR.$plugin_versions[$version]['filename'];
			if (is_file($file)) unlink($file);
			unset($plugin_versions[$version]);
			self::set_versions($slug, $plugin_versions);
		}
		return true;
	}


	#----------------------------------------------------------------------
	# Delete Plugin
	# Since: 1.0
	# A function to delete all files and db entries associated with the plugin
	#----------------------------------------------------------------------
	function del_plugin($slug) {
		global $wpdb;
		$msg = true;
		$versions = self::get_versions($slug);

		# Delete the files
		foreach ($versions as $version) {
			$file = SMP_UPLOAD_DIR.$version['filename'];
			if (is_file($file)) unlink($file);
		}

		# Delete the database data
		$success = $wpdb->query( $wpdb->prepare( 
		"
			DELETE
			FROM ".SMP_PLUGINS_TBL."
			WHERE plugin_slug = %s
		", 
		$slug
		) );

		if (!$success) $msg = __(sprintf('Could not delete %s',$slug),'sell-my-plugin');

		return $msg;
	}


	#----------------------------------------------------------------------
	# Get Plugin Details
	# Since: 1.0
	# A function to get the specific parameters from db
	# Used in: 
	#----------------------------------------------------------------------
	function get_plugin_details($PluginZip) {
		$readme_data = self::parse_readme($PluginZip);
		if (is_string($readme_data)) return $readme_data;
		$plugin_data = self::parse_plugin_data($PluginZip);
		$payload = new stdClass;
		$plugin_fields = array(
			'Slug'=>'slug',
			'Name'=>'name',
			'short_description'=>'short_description',
			'Version'=>'version',
			'Author'=>'author',
			'AuthorURI'=>'author_homepage',
			'requires_at_least'=>'requires',
			'tested_up_to'=>'tested',
			'contributors'=>'contributors',
			'tags'=>'tags',
			'upgrade_notice'=>'upgrade_notice',
			'PluginURI'=>'homepage',
			'sections'=>'sections');

		foreach ($plugin_fields as $field => $new_field) {
			if (!empty($plugin_data[$field])) {
				$payload->$new_field = $plugin_data[$field];
			}
			if (!empty($readme_data[$field])) {
				$payload->$new_field = $readme_data[$field];
			}
		}
		$payload->download_url = SMP_EP_URL.$payload->slug.'/'.$payload->version.'/';
		$payload->last_updated = date("Y-m-d H:i:s",filemtime($PluginZip));
		if (self::plugin_exists($payload->slug)) {
			$payload->rating = self::get_parameter($payload->slug, 'plugin_rating');
			$payload->num_ratings = self::get_parameter($payload->slug, 'plugin_ratings');
			$payload->downloaded = self::get_parameter($payload->slug, 'plugin_downloads');
		}
		return $payload;
	}


	#----------------------------------------------------------------------
	# Parse Readme
	# Since: 1.0
	# A function to get plugin data from the readme
	# Used in: get_plugin_details
	#----------------------------------------------------------------------
	function parse_readme($PluginZip){
		$parse_readme_path = SMP_DIR . 'lib/parse-readme.php';
		$zip = new ZipArchive;
		if ($zip->open($PluginZip) === true){
			$root = $zip->getNameIndex(0);
			$readme = $zip->getFromName($root.'readme.txt');

			$zip->close();
			if (!$readme){
				$msg = __("You must have a valid WordPress readme.txt file within the plugin zip.", 'sell-my-plugin');
				return $msg;
			}
			require_once($parse_readme_path);
			$Automattic_Readme = new Automattic_Readme();
			$plugin_info = $Automattic_Readme->parse_readme_contents($readme);
			if (!$plugin_info){
				$msg = __("Could not validate the readme.txt file.  Have you run it against the <a href='http://wordpress.org/extend/plugins/about/validator/' target='_blank'>validator</a>?", 'sell-my-plugin');
				return $msg;
			}
			else{
				if (isset($plugin_info['sections']['frequently_asked_questions'])) {
					$plugin_info['sections']['faq'] =$plugin_info['sections']['frequently_asked_questions'];
					unset($plugin_info['sections']['frequently_asked_questions']);
				}
				// let's inject the screenshots if there are any
				if (isset($plugin_info['sections']['screenshots']) and count($screenshots = self::getScreenshots($PluginZip))){
					$m = $plugin_info['sections']['screenshots'];
					$m = str_replace('<ol>','<ol class=\'screenshots\'>',$m);
					$slug = str_replace('/','',$root);
					// Why is it so difficult to get the image markup in there?
					$m = str_replace('<li>','<li><img class=\'screenshot\' src=\''.SMP_EP_URL.$slug.'/'.$plugin_info['stable_tag'].'/screenshot/__s__\' /><p>',$m);
					$m = str_replace('</li>','</p></li>',$m);
					for($s = 1; $s <= count($screenshots);$s++){
						$m = preg_replace('/__s__/',$s,$m,1);
					}
					$plugin_info['sections']['screenshots'] = $m;
				}
				if (isset($plugin_info['remaining_content'])){
				//parse remaining content into other_notes
					$plugin_info['sections']['other_notes'] = $plugin_info['remaining_content'];
					unset($plugin_info['remaining_content']);
				}
				return $plugin_info;
			}
		}
	}


	#----------------------------------------------------------------------
	# Parse Plugin Data
	# Since: 1.0
	# A function to get plugin data from the plugin.php
	# Used in: get_plugin_details
	#----------------------------------------------------------------------
	function parse_plugin_data($PluginZip,$markup = false){
		$zip = new ZipArchive;
		if ($zip->open($PluginZip) === true){
			$root = $zip->getNameIndex(0);
			if (!function_exists('get_plugin_data')){
				require_once(ABSPATH.'wp-admin/includes/plugin.php');
			}
			for($i = 0; $i < $zip->numFiles; $i++){
				$test = $zip->getNameIndex($i);
				if (dirname($test).'/' == $root and strpos(basename($test),'.php')){
					// good candidate, need to extract it to test it
					$filename = SMP_UPLOAD_DIR.'___tmp___.php';
					file_put_contents($filename,$zip->getFromIndex($i));
					$plugin_data = get_plugin_data($filename);
					$plugin_data['Slug'] = rtrim($root, '/');
					unlink($filename); // Clean up
					if ($plugin_data['Name'] != '' and $plugin_data['Version'] != ''){
						// Valid match
						$zip->close();
						return $plugin_data;
					}
				}
			}
			$zip->close();
		}
		return false;
	}


	#----------------------------------------------------------------------
	# Get Screenshots
	# Since: 1.0
	# A function to get plugin screenshots
	# Used in: 
	#----------------------------------------------------------------------
	function getScreenshots($PluginZip){
		$zip = new ZipArchive;
		$screenshots = array();
		if ($zip->open($PluginZip) === true){
			$root = $zip->getNameIndex(0);
			$no_more_screenshots = false;
			for($s=1;!$no_more_screenshots;$s++){
				$stat = false;
				$extensions = array('png' => 'image/png',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'gif' => 'image/gif');
				foreach($extensions as $extension => $mime_type) {
					if ($stat = $zip->statName($root.'screenshot-'.$s.'.'.$extension)) {
						break; 
					}
				}
				if (!$stat) { $no_more_screenshots = true; }
				else { $screenshots[$s] = $stat['name']; }
			}
		}
		return $screenshots;
	}


	#----------------------------------------------------------------------
	# Send Screenshots
	# Since: 1.0
	# A function to output the screenshots
	# Used in: 
	#----------------------------------------------------------------------
	function send_screenshot_from_zip($PluginZip,$Number){
		$zip = new ZipArchive;
		if ($zip->open($PluginZip) === true){
			$root = $zip->getNameIndex(0);
			$extensions = array('png' => 'image/png',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif');
			foreach($extensions as $extension => $mime_type) {
				if ($screenshot = $zip->getFromName($root.'screenshot-'.$Number.'.'.$extension)){
					header('Content-type: '.$mime_type);
					echo $screenshot;
					break;
				}
			}
		}
		exit();
	}


	#----------------------------------------------------------------------
	# Spoof Plugins Api
	# Since: 1.0
	# A function to spoof the plugin api
	# Used in: 
	#----------------------------------------------------------------------
	function smp_spoof_plugins_api($smp_query_var){
		if (!self::plugin_exists($smp_query_var)) {
			return new WP_Error('smp_plugin_details_failed', __('Could not find the requested plugin'));
		}
		$Plugin = self::get_plugin_by_slug($smp_query_var);
		$get_version = get_query_var(SMP_VERSION_QUERY_VAR);
		$FileName = maybe_unserialize($Plugin->plugin_versions);
		if (empty($get_version)) $get_version = key($FileName);
		$PluginZip = SMP_UPLOAD_DIR.$FileName[$get_version]['filename'];
		$plugin_data = self::get_plugin_details($PluginZip);
		$plugin_data->rating = maybe_unserialize($Plugin->plugin_rating);
		$plugin_data->num_ratings = $Plugin->plugin_ratings;
		$plugin_data->downloaded = $Plugin->plugin_downloads;
		$plugin_data->external = 'something';
		return $plugin_data;
	}


	#----------------------------------------------------------------------
	# Spoof Page
	# Since: 1.0
	# A function to spoof the page
	# Used in: 
	#----------------------------------------------------------------------
	function smp_spoof_page($Plugin,$version){
		require_once('class-spoof-page.php');
		$Spoof = new spoof_page;
		$Spoof->page_slug = $Plugin->plugin_slug;
		$Spoof->page_title = $Plugin->plugin_name;
		$content = self::getDetailsPage($Plugin->plugin_slug,$version);
		$Spoof->content = $content;
		$Spoof->post_type = 'Sell My Plugin';
		$Spoof->force_injection = true;
		if (!defined('SMP_SPOOFED_PLUGIN_PAGE')) define('SMP_SPOOFED_PLUGIN_PAGE',true);
	}


	#----------------------------------------------------------------------
	# Get Details Page
	# Since: 1.0
	# A function to Create the Details page
	# Used in: 
	#----------------------------------------------------------------------
	function getDetailsPage($slug,$version = ''){
		$url = SMP_EP_URL.$slug.($version != '' ? '/'.$version : '').'/details';
		if (!empty($_GET['section']) && $_GET['section'] != ''){
			$url.= '?section='.$_GET['section'];
		}

		$request['body'] = self::smp_install_plugin_information($slug);
		if ( is_wp_error($request) ) {
			$res = __('An Unexpected HTTP Error occurred during the API request. '). $request->get_error_message();
		} else {
			$res = $request['body'];

			$pattern=get_shortcode_regex();
			if (preg_match('/'. $pattern .'/s',$res,$matches)) {
				$matched_shortcode=$matches[0];
				$to_find = array('[',']');
				$to_replace = array('[[',']]');
				$replaced_shortcode=str_replace($to_find,$to_replace,$matched_shortcode);
				$res = str_replace($matched_shortcode,$replaced_shortcode,$res);
			}

			$res = preg_replace('/(href=\')[^\']+'.preg_quote(SMP_REDIRECT_DIR,'/').'[^\?]+\?.*(section=[^\']*)/','$1?$2'.(isset($_GET[SMP_SHORTCODE_QUERY_VAR]) ? '&'.SMP_SHORTCODE_QUERY_VAR.'='.$_GET[SMP_SHORTCODE_QUERY_VAR] : ''),$res);


			$link =SMP_EP_URL."$slug/$version/";
			$action_button = '<p class="action-button" style="margin-bottom:10px;"><a href='.$link.'>'.__('Download','sell-my-plugin').'</a></p>';
	
			$res = preg_replace('\'<!-- #action button -->\'',$action_button,$res);

			add_action('wp_footer',array('smp_plugin', 'smp_include_scripts'));
		}
		return $res;
	}


	#----------------------------------------------------------------------
	# Include Scripts
	# Since: 1.0
	# A function to include the plugin scripts
	# Used in: 
	#----------------------------------------------------------------------
	function smp_include_scripts(){
		wp_register_style('smp_stylesheet',SMP_URL.'css/smp_styles.css');
		wp_enqueue_style('smp_stylesheet');
	}


	#----------------------------------------------------------------------
	# Install Plugin Information
	# Since: 1.0
	# A function pulled from /wp-admin/includes/plugin-install.php and modified
	# Used in: 
	#----------------------------------------------------------------------
	function smp_install_plugin_information($smp_query_var) {
		$tab = 'plugin-information';
		$api = self::smp_spoof_plugins_api($smp_query_var);
		if ( is_wp_error($api) ) wp_die($api);

		$plugins_allowedtags = array(
			'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ),
			'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ),
			'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
			'div' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
			'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
			'img' => array( 'src' => array(), 'class' => array(), 'alt' => array() )
		);

		$plugins_section_titles = array(
			'description'  => _x('Description',  'Plugin installer section title'),
			'installation' => _x('Installation', 'Plugin installer section title'),
			'faq'          => _x('FAQ',          'Plugin installer section title'),
			'screenshots'  => _x('Screenshots',  'Plugin installer section title'),
			'changelog'    => _x('Changelog',    'Plugin installer section title'),
			'other_notes'  => _x('Other Notes',  'Plugin installer section title')
		);

		//Sanitize HTML
		foreach ( (array)$api->sections as $section_name => $content )
			$api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);
		foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
			if ( isset( $api->$key ) )
				$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
		}

		$section = isset($_REQUEST['section']) ? stripslashes( $_REQUEST['section'] ) : 'description'; 
		//Default to the Description tab, Do not translate, API returns English.
		if ( empty($section) || ! isset($api->sections[ $section ]) )
			$section = array_shift( $section_titles = array_keys((array)$api->sections) );

		$return = "<div id='plugin-information'>\n";
		$return .= "<div id='$tab-header'>\n";
		$return .= "<ul id='sidemenu'>\n";
		foreach ( (array)$api->sections as $section_name => $content ) {

			if ( isset( $plugins_section_titles[ $section_name ] ) )
				$title = $plugins_section_titles[ $section_name ];
			else
				$title = ucwords( str_replace( '_', ' ', $section_name ) );

			$class = ( $section_name == $section ) ? ' class="current"' : '';
			$href = add_query_arg( array('tab' => $tab, 'section' => $section_name) );
			$href = esc_url($href);
			$san_section = esc_attr( $section_name );
			$return .= "\t<li><a name='$san_section' href='$href' $class>$title</a></li>\n";
		}
		$return .= "</ul>\n";
		$return .= "</div>\n";
		$return .= "<div class='alignright fyi'>\n";
		$return .= "<!-- #action button -->\n";
		if ( ! empty($api->download_link) && ( current_user_can('install_plugins') || current_user_can('update_plugins') ) ) :
			$return .= "<p class='action-button'>\n";
			$return .= "</p>\n";

		endif;

		$return .= "<h2 class='mainheader'>".__('FYI')."</h2>\n";
		$return .= "<ul>\n";
		if ( ! empty($api->version) ) :
			$return .= "<li><strong>".__('Version:')."</strong> ".$api->version."</li>\n";
		endif; if ( ! empty($api->author) ) :
			$return .= "<li><strong>".__('Author:')."</strong> ".links_add_target($api->author, '_blank')."</li>\n";
		endif; if ( ! empty($api->last_updated) ) :
			$return .= "<li><strong>".__('Last Updated:')."</strong> <span title='".$api->last_updated."'>";
			$return .= sprintf( __('%s ago'), human_time_diff(strtotime($api->last_updated)) )."</span></li>\n";
		endif; if ( ! empty($api->requires) ) :
			$return .= "<li><strong>".__('Requires WordPress Version:')."</strong> ".sprintf(__('%s or higher'), $api->requires)."</li>\n";
		endif; if ( ! empty($api->tested) ) :
			$return .= "<li><strong>".__('Compatible up to:')."</strong> ".$api->tested."</li>\n";
		endif; if ( ! empty($api->downloaded) ) :
			$return .= "<li><strong>".__('Downloaded:')."</strong>".sprintf(_n('%s time', '%s times', $api->downloaded), number_format_i18n($api->downloaded))."</li>\n";
		endif; if ( ! empty($api->slug) && empty($api->external) ) :
			$return .= "<li><a target='_blank' href='http://wordpress.org/extend/plugins/".$api->slug."/'>".__('WordPress.org Plugin Page &#187;')."</a></li>\n";
		endif; if ( ! empty($api->homepage) ) :
			$return .= "<li><a target='_blank' href='".$api->homepage."'>".__('Plugin Homepage &#187;')."</a></li>\n";
		endif;
		$return .= "</ul>\n";
		//$api->rating = maybe_unserialize($api->rating);
		if ( ! empty($api->rating) && intval($api->num_ratings) != 0) :
			$return .= "<h2>".__('Average Rating')."</h2>\n";
			$return .= "<div class='star-holder' title='".sprintf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings))."'>\n";
			$rating = $api->rating;
			$ratings = self::get_rating($api->rating,$api->num_ratings,92);
			$return .= "<div class='star star-rating' style='width: ".$ratings->star_width."px'></div>";
			$return .= "</div>\n";
			$return .= "<small>".sprintf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings))."</small><br>\n";

			for ($i=5; $i >= 1; $i--) {
				$return .= "<div class='counter-container'>\n";
				$return .= "<span class='counter-label' style='float:left; margin-right:5px;'>$i stars</span>\n";
				$return .= "<div class='counter-back' style='height:17px;width:92px;background-color:#ececec;float:left;'>\n";
				$return .= "<div class='counter-bar' style='width: ".(intval($rating[$i]) / intval($api->num_ratings)) * 92 ."px;height:17px;background-color:#fddb5a;float:left;'>";
				$return .= "</div>\n";
				$return .= "</div>\n";
				$return .= "<span class='counter-count' style='margin-left:5px;'>".$rating[$i]."</span>\n";
				$return .= "</div>\n";

			}
		endif; 

		$return .= "<!-- #my rating -->\n";

		$return .= "</div>\n";

		$return .= "<div id='section-holder' class='wrap'>\n";

		foreach ( (array)$api->sections as $section_name => $content ) {
			if ( isset( $plugins_section_titles[ $section_name ] ) )
				$title = $plugins_section_titles[ $section_name ];
			else
				$title = ucwords( str_replace( '_', ' ', $section_name ) );
			$content = links_add_base_url($content, 'http://wordpress.org/extend/plugins/' . $api->slug . '/');
			$content = links_add_target($content, '_blank');
			$san_section = esc_attr( $section_name );
			$display = ( $section_name == $section ) ? 'block' : 'none';
			$return .= "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
			$return .= "\t\t<h2 class='long-header'>$title</h2>\n";
			$return .= $content;
			$return .= "\t</div>\n";
		}
		$return .= "</div>\n";
		$return .= "</div>\n";
		return $return;
	}


	#----------------------------------------------------------------------
	# Search Transaction ID
	# Since: 1.0
	# A function to search for a transaction id
	# Used in: 
	#----------------------------------------------------------------------
	function search_tid($slug,$tid) {
	}


	#----------------------------------------------------------------------
	# Free Key
	# Since: 1.0
	# A function Generate a free key
	# Used in: 
	#----------------------------------------------------------------------
	function free_key($slug) {
	}


	#----------------------------------------------------------------------
	# List Plugins
	# Since: 1.0
	# A function to list all of the plugins
	# Used in: 
	#----------------------------------------------------------------------
	function list_plugins($show_plugin = null) {
		global $wpdb;

		if ($show_plugin != null) $_REQUEST['q'] = $show_plugin;
		if (!empty($_REQUEST['q']) && $q = '\'%'.$_REQUEST['q'].'%\'') { 
		  $query = "
			SELECT * 
			FROM ".SMP_PLUGINS_TBL."
			WHERE plugin_slug LIKE $q
				OR plugin_name LIKE $q
				OR plugin_price LIKE $q
				OR plugin_id LIKE $q
			";
	          $plugins = $wpdb->get_results($query, OBJECT);
		} else {
	          $plugins = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".SMP_PLUGINS_TBL.""), OBJECT);
	        }
		$return = '';
		$all_downloads = 0;

		foreach ($plugins as $plugin) {
			$plugin->plugin_rating = maybe_unserialize($plugin->plugin_rating);
			if (get_option('smp_api_sandbox') == 'TRUE') {
				$plugin = self::get_plugin_sandbox($plugin);
			}
			$all_downloads = $all_downloads + intval($plugin->plugin_downloads);
			$Versions = maybe_unserialize($plugin->plugin_versions);
			$version = key($Versions);
			$PluginZip = SMP_UPLOAD_DIR . $Versions[$version]['filename'];
			$details = self::get_plugin_details($PluginZip);

			if ($plugin->plugin_ratings != '0') {
				$rating = self::get_rating($plugin->plugin_rating,$plugin->plugin_ratings,92);
			} else {
				$rating->star_width = $rating->stars = 0;
			}

			if (empty($plugin->plugin_price) || ($plugin->plugin_price == 0))
				$plugin->plugin_price = "FREE";
			else
				$plugin->plugin_price = "$".$plugin->plugin_price;

			$updated = $plugin->plugin_modified_timestamp;
			list($updated,$utime) = explode(' ',$updated);
			$return .= '<div class="plugin-block">';
			$return .= "<h3><a href=".SMP_EP_URL. $plugin->plugin_slug.">". $plugin->plugin_name."</a></h3>";
			$return .= $details->short_description;
			$return .= "<ul class='plugin-meta'>";
			$return .= "<li><span class='info-marker'>Version</span> $version</li>";
			$return .= "<li><span class='info-marker'>Updated</span> $updated</li>";
			$return .= "<li><span class='info-marker'>Downloads</span> ".$plugin->plugin_downloads."</li>";
			$return .= "<li><span class='info-marker'>Price</span> ".$plugin->plugin_price."</li>";
			$return .= "<li><span class='info-marker left'>Average Rating</span>";
			$return .= "<div class='star-holder'>";
			$return .= "<div class='star-rating' style='width: ".$rating->star_width."px'>".$rating->stars." stars</div>";
			$return .= "</div>";
			$return .= "</li>";
			$return .= "</ul>";
			$return .= '<br class=clear>';
			$return .= '</div>';
		}
		$return .= '</div>';
		$header = '<div id=plugin-information>';
		if (@!$q) { 
			$header .= "<div class='intro'>".get_option('smp_plugins_heading')."</div>";
			$header .= "<h3 id=count><strong>".count($plugins)."</strong> PLUGINS, ";
			$header .= "<strong>$all_downloads</strong> DOWNLOADS, AND COUNTING</h3>"; 
		}
		$header .= "<form action='".SMP_EP_URL."' method='get' id='plugins-search'>";
		$header .= "<p><input type='text' class='text' maxlength='100' name='q' value=''>";
		$header .= "<input type='submit' value='Search Plugins' class='button'></p>";
		$header .= "</form>";
		if (!$show_plugin) { $return = $header . $return; }
		else {$return = '<div id=plugin-information>'.$return;}
		return $return;
	}


	#----------------------------------------------------------------------
	# Delete Sandbox Transactions
	# Since: 1.0
	# A function to delete all sandbox transactions
	# Used in: 
	#----------------------------------------------------------------------
	function del_sandbox_txns() {
	}


	#----------------------------------------------------------------------
	# Add Plugin Page
	# Since: 1.0
	# A function to add a page for each plugin
	# Used in: 
	#----------------------------------------------------------------------
	function add_plugin_page($data) {
	}


	#----------------------------------------------------------------------
	# Get Page by name
	# Since: 1.0
	# A function to get a page using its name
	# Used in: NONE
	#----------------------------------------------------------------------
	function get_page_by_name($pagename) {
	}


	#----------------------------------------------------------------------
	# Get plugin Sandbox
	# Since: 1.0
	# A function to get data from sandbox column
	# Used in: 
	#----------------------------------------------------------------------
	function get_plugin_sandbox($plugin) {
	}


	#----------------------------------------------------------------------
	# Set Plugin Sandbox
	# Since: 1.0
	# A function to set the sandbox data
	# Used in: 
	#----------------------------------------------------------------------
	function set_plugin_sandbox($plugin) {
	}


	#----------------------------------------------------------------------
	# Get Rating
	# Since: 1.0
	# A function to perform all functions on the rating data and return as obj
	# Used in: 
	#----------------------------------------------------------------------
	function get_rating($plugin_rating,$plugin_ratings,$width) {
		$rating->total = (intval($plugin_rating['5'])*5) + # total rating
				(intval($plugin_rating['4'])*4) + 
				(intval($plugin_rating['3'])*3) + 
				(intval($plugin_rating['2'])*2) + 
				intval($plugin_rating['1']);

		$rating->max = intval($plugin_ratings) * 5; # Max possible rating based on # of ratings.
		$rating->percent = $rating->total / $rating->max;
		$rating->star_width = ($rating->percent) * $width; # gets the width of the stars.
		$rating->stars = ($rating->percent) *5;

		return $rating;
	}


	#----------------------------------------------------------------------
	# Do rating
	# Since: 1.0
	# A function to perform rating
	# Used in: 
	#----------------------------------------------------------------------
	function do_rating($slug, $rate) {
		$plugin->plugin_slug = $slug;
		if (get_option('smp_api_sandbox') == 'FALSE') {
			$plugin->plugin_rating = maybe_unserialize(self::get_parameter($plugin->plugin_slug, 'plugin_rating'));
			if (empty($plugin->plugin_rating))
				$plugin->plugin_rating = array('5'=>0, '4'=>0, '3'=>0, '2'=>0, '1'=>0);
			$plugin->plugin_ratings = self::get_parameter($plugin->plugin_slug, 'plugin_ratings');
		} else {
			$plugin = self::get_plugin_sandbox($plugin);
		}

		$plugin_rating = $plugin->plugin_rating;

		if (isset($plugin_rating[intval($_GET['rate'])])) {
			$plugin_rating[intval($_GET['rate'])] = $plugin_rating[intval($_GET['rate'])] +1;
		} else {
			$plugin_rating[intval($_GET['rate'])] = 1;
		}

		$plugin->plugin_rating = $plugin_rating;

		$plugin->plugin_ratings = intval($plugin->plugin_ratings)+1;
		if (get_option('smp_api_sandbox') == 'FALSE') {
			self::set_parameter($plugin->plugin_slug, 'plugin_rating', serialize($plugin->plugin_rating));
			self::set_parameter($plugin->plugin_slug, 'plugin_ratings', $plugin->plugin_ratings);
		} else {
			self::set_plugin_sandbox($plugin);
		}
		return;
	}

} # End Class
} # End If
?>
