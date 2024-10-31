<?php
namespace PostReplicator;

class Connector
{
	private $id;

	private $username;

	private $url;

	private $password;

	private $posts_url;

	private $media_url;

	private $categories_url;

	private $categories_per_page;

	private $categories_config;

	private $tags_url;

	private $tested;

	private $remote_cats;

	private $categories_main_post;

	private $created_at;

	private $updated_at;

	public function __construct($website_id)
	{
		$website = DB::get_website($website_id);
		$this->id = $website->id;
		$this->username = $website->username;
		$this->url = $website->url;
		$this->password = $website->app_password;
		$this->posts_url = $website->posts_url;
		$this->media_url = $website->media_url;
		$this->categories_url = $website->categories_url;
		$this->categories_per_page = 50;
		$this->categories_config = unserialize($website->categories_config);
		$this->tags_url = $website->tags_url;
		$this->tested = $website->tested;
		$this->created_at = $website->created_at;
		$this->updated_at = $website->updated_at;
	}

	public function get_id()
	{
		return $this->id;
	}

	public function get_remote_cats()
	{
		// retrive the remote cats
    	$remote_cats_response = wp_remote_get($this->categories_url.'?per_page='.$this->categories_per_page, array());
    	if(is_wp_error($remote_cats_response)){
    		$remote_cats = array();
    	} else {
    		$remote_cats_raw = json_decode($remote_cats_response['body']);
    		$remote_cats = array();
    		foreach ($remote_cats_raw as $cat) {
    			$remote_cats[$cat->slug] = array(
    				'term_id' => $cat->id,
    				'name' => $cat->name
    			);
    		}
    	}

    	$this->remote_cats = $remote_cats;
	}

	public function get_categories_config()
	{
		return $this->categories_config;
	}

	public function set_main_post_categories($cats)
	{
		$this->categories_main_post = $cats;
	}

	public function maybe_create_remote_cats()
	{
		// create cats remote in case that they not exists
    	$categories_processed = array();
    	foreach ($this->categories_main_post as $catslug => $values) {
    		if(!isset($this->remote_cats[$catslug])){
    			$remote_cat_create = wp_remote_post($this->categories_url, array(
    				'headers' => array(
    					'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password)
    				),
    				'body' => array(
    					'name' => $values['name'],
    					'slug' => $catslug
    				)
    			));
    			if(!is_wp_error($remote_cat_create)){
    				$remote_cat = json_decode($remote_cat_create['body']);
    				$categories_processed[] = $remote_cat;
    			}
    		}
    	}

    	// retrive again the cats, with the new one's created
    	if(count($categories_processed) > 0)
    		$this->get_remote_cats();
	}

	/**
	 * Match the term_id for the remote categories with the local categories
	 * meas that if the local category id for "movies" is 6, the find in the remote categories
	 * the id of the "movies" categories and set this for the categories for post
	 */
	public function normalize_categories()
	{
		$categories = array();
    	foreach ($this->categories_main_post as $catslug => $values) {
    		$categories[] = $this->remote_cats[$catslug]['term_id'];
    	}
    	return $categories;

	}

	private function with_cats_not_allowed()
	{
		// check if the post has categories not allowed
		$allowed = false;
		foreach ($this->categories_main_post as $slug => $value) {
			if(isset($this->categories_config[$slug]) && $this->categories_config[$slug] == 'no'){
				$allowed = true;
				break;
			}
		}
		return $allowed;
	}

	public function create_remote_post($website_id, $data, $local_id)
	{
		// if the post has categories not allowed then just do nothing and return false
		if($this->with_cats_not_allowed())
			return false;
		// send the post data
    	$response = wp_remote_post($this->posts_url, array(
    		'headers' => array(
				'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password)
			),
			'body' => $data
    	));

    	if(is_wp_error($response)){
    		return false;
    	}

    	$remote = json_decode($response['body']);

    	$post_id = DB::insert_post($local_id, $remote->id, serialize($data['categories']));
    	DB::insert_relation_website_post($website_id, $post_id);

    	return $remote;
	}

	public function update_remote_post($website_id, $data, $local_data)
	{
		// if the post has categories not allowed we need to delete this on the remote
		if($this->with_cats_not_allowed()){
			wp_remote_request($this->posts_url.DIRECTORY_SEPARATOR.$local_data->remote_post_id, array(
				'method' => 'DELETE',
				'headers' => array(
					'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password)
				),
			));
			// after being delete from the remote, delete too in the local records
			DB::delete_post($website_id, $local_data->id);
			return false;
		}

		$response = wp_remote_post($this->posts_url.DIRECTORY_SEPARATOR.$local_data->remote_post_id, array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password)
			),
			'body' => $data
		));

		if(is_wp_error($response)){
    		return false;
    	}

    	$remote = json_decode($response['body']);

    	return $remote;

	}

	public function update_local_media($thumb, $local_data)
	{
		$thumb_parts = explode('/', $thumb);
    	$thumb_name = array_pop($thumb_parts);

    	$feat_local_id = get_post_thumbnail_id($local_data->local_post_id);

    	// if the local feat media has not changed, then return false
    	if(intval($local_data->feat_local_id) === $feat_local_id){
    		return false;
    	}

    	$thumb_remote =  wp_remote_post($this->media_url, array(
    		'headers' => array(
				'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password),
				'Content-Disposition' => 'form-data; filename="'.$thumb_name.'"'
			),
			'body' => @file_get_contents($thumb),
			'timeout' => 24
    	));
    	
    	// if the media post false return false
    	if(is_wp_error($thumb_remote))
        	return false;

        $thumb_remote_data = json_decode($thumb_remote['body']);

        DB::update_post_feat($local_data->local_post_id, $feat_local_id, $thumb_remote_data->id);

        return $thumb_remote_data;
	}

	public function set_remote_post_media($post_remote_id, $thumb, $thumb_remote)
	{
		$thumb_parts = explode('/', $thumb);
    	$thumb_name = array_pop($thumb_parts);
    	
		return wp_remote_post($this->posts_url.DIRECTORY_SEPARATOR.$post_remote_id, array(
    		'headers' => array(
				'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password),
				'Content-Disposition' => 'form-data; filename="'.$thumb_name.'"'
			),
			'body' => array(
				'featured_media' => $thumb_remote->id
			)
    	));
	}

	public function test()
	{
		$response = wp_remote_post($this->posts_url, array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode($this->username.':'.$this->password)
			),
			'body' => array(
				'title' => 'Test Title',
				'content' => 'Test content',
				'status' => 'draft'
			)
		));

		if(is_wp_error($response) || (isset($response['response']) && !in_array($response['response']['code'], array(200, 201, 202))) ){
			DB::update_website_field($this->id, 'tested', 0);
		} else {
			DB::update_website_field($this->id, 'tested', 1);
		}
	}
}