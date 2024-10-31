<?php
namespace PostReplicator;

class Route
{
	private static $is_auto_save = false;

	public static function process_requests()
	{
		if(current_user_can('manage_options') 
			&& isset($_POST['post_replicator_alias'])
			&& is_string($_POST['post_replicator_alias'])
			&& isset($_POST['post_replicator_url'])
			&& is_string($_POST['post_replicator_url'])
			&& isset($_POST['post_replicator_username'])
			&& is_string($_POST['post_replicator_username'])){

			$alias = sanitize_text_field($_POST['post_replicator_alias']);
			$url = sanitize_text_field($_POST['post_replicator_url']);
			$username = sanitize_text_field($_POST['post_replicator_username']);
			$base_rest = '/wp-json';
			$namespace = '/wp/v2';
			$posts_url = $url.$base_rest.$namespace.'/posts';
			$media_url = $url.$base_rest.$namespace.'/media';
			$categories_url = $url.$base_rest.$namespace.'/categories'; //?per_page=50
			$tags_url = $url.$base_rest.$namespace.'/tags';

			// create a new website
			if(!isset($_POST['post_replicator_id']) 
				&& isset($_POST['post_replicator_password']) 
				&& is_string($_POST['post_replicator_password'])
				&& ($password = sanitize_text_field($_POST['post_replicator_password'])) ){
				$cat_configs = serialize(array());
				DB::insert_website($alias, $url, $username, $password, $posts_url, $media_url, $categories_url, $cat_configs, $tags_url);
			// update an existent website
			} else if(isset($_POST['post_replicator_id']) 
				&& is_numeric($_POST['post_replicator_id']) 
				&& ($id = intval($_POST['post_replicator_id'])) ){
				// update an existent website with the password
				if(isset($_POST['post_replicator_password']) 
					&& is_string($_POST['post_replicator_password']) 
					&& ($password = sanitize_text_field($_POST['post_replicator_password'])) ){
					DB::update_website_with_password($id, $alias, $url, $username, $password, $posts_url, $media_url, $categories_url, $tags_url);
				}
				// update an existent website without password
				DB::update_website_without_password($id, $alias, $url, $username, $posts_url, $media_url, $categories_url, $tags_url);
			}

			wp_redirect(get_admin_url().'admin.php?page=post-replicator');
			exit;
		}

		if(current_user_can('manage_options')
			&& isset($_POST['postreplicator_action'])
			&& is_string($_POST['postreplicator_action'])
			&& isset($_POST['postreplicator_website'])	
			&& is_numeric($_POST['postreplicator_website'])
			&& ($action = sanitize_text_field($_POST['postreplicator_action'])) 
			&& ($id = intval($_POST['postreplicator_website'])) ){

			if($action == 'test'){
				(new Connector($id))->test();
			} else if ($action == 'delete'){
				DB::delete_website($id);
				wp_redirect(get_admin_url().'admin.php?page=post-replicator');
				exit;
			}
		}


		if(current_user_can('manage_options')
			&& isset($_GET['update_cats'])
			&& $_GET['update_cats'] == true
			&& isset($_POST['postreplicator_website'])
			&& isset($_POST['postreplicator_cats']) 
			&& ($web_id = intval($_POST['postreplicator_website'])) 
			&& ($cats_config = is_array($_POST['postreplicator_cats']) ? $_POST['postreplicator_cats'] : false)){

			$cats = serialize($cats_config);
			DB::update_cats_website($web_id, $cats);
			wp_redirect(get_admin_url().'admin.php?page=post-replicator&config=true&website='.$web_id);
			exit;
		}

		if(current_user_can('manage_options') 
			&& isset($_POST['data']) 
			&& isset($_POST['data']['wp_autosave'])
			&& isset($_POST['data']['wp_autosave']['auto_draft'])
			&& $_POST['data']['wp_autosave']['auto_draft'] == "1" ){
			static::$is_auto_save = true;
		}

	}

	public static function is_edit_website()
	{
		if(isset($_GET['edit']) && isset($_GET['website']) && $_GET['edit'] == true && is_numeric($_GET['website']))
			return true;
		else
			return false;
	}

	public static function is_config_website()
	{
		if(isset($_GET['config']) && isset($_GET['website']) && $_GET['config'] == true && is_numeric($_GET['website']))
			return true;
		else
			return false;
	}

	public static function is_auto_save($post_id)
	{
		return static::$is_auto_save || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || get_the_title($post_id) === 'Auto Draft';
	}

	public static function get_edit_website()
	{
		return intval($_GET['website']);
	}

	public static function get_config_website()
	{
		return intval($_GET['website']);	
	}
}