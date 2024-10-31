<?php
namespace PostReplicator;

class DB
{
	private static $wpdb = null;
	private static $charset;
	private static $posts_table_name;
	private static $websites_table_name;
	private static $post_website_table_relation_name;

	public static function boot()
	{
		if(static::$wpdb === null){
			global $wpdb;
			static::$wpdb =& $wpdb;
			static::$charset = static::$wpdb->get_charset_collate();
			static::$posts_table_name = static::$wpdb->prefix.'postreplicator_posts';
			static::$websites_table_name = static::$wpdb->prefix.'postreplicator_websites';
			static::$post_website_table_relation_name = static::$wpdb->prefix.'postreplicator_post_website';
		}
	}

	private static function get_current_date()
	{
		$current_date = current_datetime();
		return $current_date->format("Y-m-d H:i:s");
	}

	public static function get_websites()
	{
		$websites_name = static::$websites_table_name;
		return static::$wpdb->get_results(static::$wpdb->prepare("SELECT id, alias, username, url, app_password, posts_url, media_url, categories_url, tags_url, tested, created_at FROM $websites_name"));	
	}

	public static function get_website($id)
	{
		$websites_name = static::$websites_table_name;
		return static::$wpdb->get_row(static::$wpdb->prepare("SELECT id, alias, username, url, app_password, posts_url, media_url, categories_url, categories_config, tags_url, tested, created_at, updated_at FROM $websites_name WHERE id = %d", $id));
	}

	public static function get_post_by_local($website_id, $post_id)
	{
		$posts_name = static::$posts_table_name;
		$post_website_name = static::$post_website_table_relation_name;
		return static::$wpdb->get_row(static::$wpdb->prepare("SELECT $posts_name.id, local_post_id, remote_post_id, categories, feat_local_id, feat_remote_id, created_at, updated_at FROM $posts_name JOIN $post_website_name ON $posts_name.id = $post_website_name.post_id WHERE $posts_name.local_post_id = %d AND $post_website_name.website_id = %d", $post_id, $website_id));
	}

	public static function post_exists($local_id)
	{
		$posts_name = static::$posts_table_name;
		return static::$wpdb->get_row(static::$wpdb->prepare("SELECT id, local_post_id, remote_post_id FROM $posts_name WHERE local_post_id = %d", $local_id));
	}

	public static function insert_website($alias, $url, $username, $password, $posts_url, $media_url, $categories_url, $categories_config, $tags_url)
	{
		$websites_name = static::$websites_table_name;
		static::$wpdb->insert(
			$websites_name,
			array(
				'alias' => $alias,
				'username' => $username,
				'url' => $url,
				'app_password' => $password,
				'posts_url' => $posts_url,
				'media_url' => $media_url,
				'categories_url' => $categories_url,
				'categories_config' => $categories_config,
				'tags_url' => $tags_url,
				'tested' => 0,
				'created_at' => static::get_current_date(),
				'updated_at' => static::get_current_date()
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
		);
	}

	public static function insert_post($local_id, $remote_id, $categories)
	{
		$posts_name = static::$posts_table_name;
		static::$wpdb->insert(
			$posts_name,
			array(
				'local_post_id' => $local_id,
				'remote_post_id' => $remote_id,
				'categories' => $categories,
				'created_at' => static::get_current_date(),
				'updated_at' => static::get_current_date()
			),
			array('%d', '%d', '%d', '%s', '%s', '%s')
		);

		return static::$wpdb->insert_id;
	}

	public static function insert_relation_website_post($website_id, $post_id)
	{
		$post_website_name = static::$post_website_table_relation_name;
		static::$wpdb->insert(
			$post_website_name,
			array(
				'website_id' => $website_id,
				'post_id' => $post_id
			),
			array('%d', '%d')
		);
	}

	public static function update_website_with_password($id, $alias, $url, $username, $password, $posts_url, $media_url, $categories_url, $tags_url)
	{
		$websites_name = static::$websites_table_name;
		static::$wpdb->update(
			$websites_name,
			array(
				'alias' => $alias,
				'username' => $username,
				'url' => $url,
				'app_password' => $password,
				'posts_url' => $posts_url,
				'media_url' => $media_url,
				'categories_url' => $categories_url,
				'tags_url' => $tags_url,
				'tested' => 0,
				'updated_at' => static::get_current_date()
			),
			array('id' => $id),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
		);
	}

	public static function update_website_without_password($id, $alias, $url, $username, $posts_url, $media_url, $categories_url, $tags_url)
	{
		$websites_name = static::$websites_table_name;
		static::$wpdb->update(
			$websites_name,
			array(
				'alias' => $alias,
				'username' => $username,
				'url' => $url,
				'posts_url' => $posts_url,
				'media_url' => $media_url,
				'categories_url' => $categories_url,
				'tags_url' => $tags_url,
				'updated_at' => static::get_current_date()
			),
			array('id' => $id),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
		);
	}

	public static function update_website_field($id, $field, $value)
	{
		$websites_name = static::$websites_table_name;
		$format = '%s';
		if(is_numeric($value)){
			$format = '%d';
		} else if(is_string($value)){
			$format = '%s';
		}
		static::$wpdb->update(
			$websites_name,
			array(
				$field => $value
			),
			array('id' => $id),
			array($format)
		);
	}

	public static function update_post_feat($local_post_id, $local_feat_id, $remote_feat_id)
	{
		$posts_name = static::$posts_table_name;
		static::$wpdb->update(
			$posts_name,
			array(
				'feat_local_id' => $local_feat_id,
				'feat_remote_id' => $remote_feat_id
			),
			array('local_post_id' => $local_post_id),
			array('%d', '%d')
		);
	}

	public static function update_cats_website($id, $cats)
	{
		$websites_name = static::$websites_table_name;
		static::$wpdb->update(
			$websites_name,
			array(
				'categories_config' => $cats
			),
			array('id' => $id),
			array('%s')
		);
	}

	public static function delete_website($id)
	{
		$websites_name = static::$websites_table_name;
		static::$wpdb->delete($websites_name, array('id' => $id));
	}

	public static function delete_post($website_id, $post_id)
	{
		$posts_name = static::$posts_table_name;
		$post_website_name = static::$post_website_table_relation_name;
		static::$wpdb->query(static::$wpdb->prepare("DELETE FROM $posts_name JOIN $post_website_name ON $posts_name.id = $post_website_name.post_id WHERE $post_website_name.post_id = %d AND $post_website_name.website_id = %d", $post_id, $website_id));
	}

	public static function create_tables()
	{
		require_once ABSPATH."wp-admin/includes/upgrade.php";
		$posts_name = static::$posts_table_name;
		$charset = static::$charset;
		if(!static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $posts_name))){
			$posts_sql = "CREATE TABLE $posts_name(
							id bigint(20) unsigned NOT NULL auto_increment,
							local_post_id bigint(20) unsigned NOT NULL default 0,
							remote_post_id bigint(20) unsigned NOT NULL default 0,
							categories varchar(400) NOT NULL default '',
							feat_local_id bigint(20) unsigned NOT NULL default 0,
							feat_remote_id bigint(20) unsigned NOT NULL default 0,
							created_at datetime NOT NULL default '1000-01-01 00:00:00',
							updated_at datetime NOT NULL default '1000-01-01 00:00:00',
							PRIMARY KEY  (id)
						) $charset";

			dbDelta($posts_sql);
		}

		$websites_name = static::$websites_table_name;
		if(!static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $websites_name))){
			$websites_sql = "CREATE TABLE $websites_name(
							id bigint(20) unsigned NOT NULL auto_increment,
							alias varchar(255) NOT NULL default '',
							username varchar(255) NOT NULL default '',
							url varchar(255) NOT NULL default '',
							app_password varchar(80) NOT NULL default '',
							posts_url varchar(255) NOT NULL default '',
							media_url varchar(255) NOT NULL default '',
							categories_url varchar(255) NOT NULL default '',
							categories_config varchar(800) NOT NULL default '',
							tags_url varchar(255) NOT NULL default '',
							tested tinyint(3) NOT NULL default 0,
							created_at datetime NOT NULL default '1000-01-01 00:00:00',
							updated_at datetime NOT NULL default '1000-01-01 00:00:00',
							PRIMARY KEY  (id)
						) $charset";

			dbDelta($websites_sql);
		}

		$post_website_name = static::$post_website_table_relation_name;
		if(!static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $post_website_name))){
			$post_website_sql = "CREATE TABLE $post_website_name(
							id bigint(20) unsigned NOT NULL auto_increment,
							website_id bigint(20) unsigned NOT NULL default 0,
							post_id bigint(20) unsigned NOT NULL default 0,
							PRIMARY KEY  (id),
							KEY website_id (website_id),
							KEY post_id (post_id)
						) $charset";

			dbDelta($post_website_sql);
		}
	}

	public static function drop_tables()
	{
		$posts_name = static::$posts_table_name;
		if(static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $posts_name)))
			static::$wpdb->query("DROP TABLE $posts_name");
		$websites_name = static::$websites_table_name;
		if(static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $websites_name)))
			static::$wpdb->query("DROP TABLE $websites_name");
		$post_website_name = static::$post_website_table_relation_name;
		if(static::$wpdb->query(static::$wpdb->prepare("SHOW TABLES LIKE %s", $post_website_name)))
			static::$wpdb->query("DROP TABLE $post_website_name");
	}
}