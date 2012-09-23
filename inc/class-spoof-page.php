<?php
/*
	Spoof Page Class

	Copyright (c) 2012 by Rob Landry
*/
/******************************************************************************
/	Spoof Page Class
/*****************************************************************************/
if (!class_exists('spoof_page')) {
class spoof_page {


	#----------------------------------------------------------------------
	# Variables
	# Since: 1.0
	#----------------------------------------------------------------------
	var $page_slug = 'plugin';
	var $page_title = 'plugin';
	var $ping_status = 'open';
	var $content = 'the content';
	var $force_injection = false;


	#----------------------------------------------------------------------
	# The Constructor
	# Since: 1.0
	# A function to construct the class
	#----------------------------------------------------------------------
	function __construct() {
		add_filter('the_posts',array(&$this,'check_post'));
	}


	#----------------------------------------------------------------------
	# Check Post
	# Since: 1.0
	# A function to Check if WP has this post
	#----------------------------------------------------------------------
	function check_post($posts) {
		global $wp;
		global $wp_query;
		if (strtolower($wp->request) == strtolower($this->page_slug) ||
		 (array_key_exists('page_id',$wp->query_vars) && $wp->query_vars['page_id'] == $this->page_slug ) || 
		 $this->force_injection){
			$posts=NULL;
			$posts[]=$this->new_post();
			$wp_query->is_page = true;
			$wp_query->is_singular = true;
			$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			unset($wp_query->query["error"]);
			$wp_query->query_vars["error"]="";
			$wp_query->is_404=false;
		}
		return $posts;
	}


	#----------------------------------------------------------------------
	# New Post
	# Since: 1.0
	# A function to construct the new Post
	#----------------------------------------------------------------------
	function new_post() {
		$post = new stdClass;
		$post->post_author = 1;
		$post->post_name = $this->page_slug;
		$post->guid = get_bloginfo('wpurl') . '/' . $this->page_slug;
		$post->post_title = $this->page_title;
		$post->post_content = $this->get_content();
		$post->ID = -1;
		$post->post_status = 'static';
		$post->comment_status = 'closed';
		$post->ping_status = $this->ping_status;
		$post->comment_count = 0;
		$post->post_date = current_time('mysql');
		$post->post_date_gmt = current_time('mysql', 1);
		$post->post_type = $this->post_type;
		$post = apply_filters('create_fake_post',$post);
		return($post);		
	}


	#----------------------------------------------------------------------
	# Get the Content
	# Since: 1.0
	# A function to set the content
	#----------------------------------------------------------------------
	function get_content() {
		return $this->content;
	}
} # End Class
} # End If

?>
