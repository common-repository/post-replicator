<?php
/**
 * Plugin Name: Post Replicator
 * Description: Replicate posts to multiple websites, this are replicated on base of the allowed categories.
 * Tags: replication, post, posts
 * Author: TocinoDev
 * Author URI: https://tocino.mx
 * Version: 0.1.0
 * Tested up to: 6.1
 * Requires PHP: 7.4
 */
use PostReplicator\App as PostReplicatorApp;

defined('ABSPATH') || exit();

if(!defined('POSTREPLICATOR_FILE')){
	define('POSTREPLICATOR_FILE', __FILE__);
}
if(!defined('POSTREPLICATOR_URL')){
	define('POSTREPLICATOR_URL', plugin_dir_url(POSTREPLICATOR_FILE));
}

require 'vendor/autoload.php';

PostReplicatorApp::boot();
