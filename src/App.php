<?php
namespace PostReplicator;

class App
{
	public static $instance = null;

	public function __construct()
	{
		$admin = new Admin();

		register_activation_hook(POSTREPLICATOR_FILE, array(Installation::class, 'install'));
		register_deactivation_hook(POSTREPLICATOR_FILE, array(Installation::class, 'uninstall'));

		add_action('admin_menu', array($admin, 'register_admin_menu'));
		add_action('admin_init', array(Route::class, 'process_requests'));
        //add_action('wp_creating_autosave', array(static::class, 'check_post'));
		add_action('save_post', array(static::class, 'save_post'), 20, 3);
	}

	public static function boot()
	{
		if(static::$instance === null){
			DB::boot();
			static::$instance = new static;
		}
	}

	public static function save_post($post_id, $post, $update)
	{
        if(Route::is_auto_save($post_id))
            return;

        $categories_normalized = Helpers::get_categories($post_id);
        
        // prepare the data to send to the remote post
        $data_post = array(
        	'title' => $post->post_title,
        	'content' => $post->post_content,
        	'status' => $post->post_status
        );
        // get all the current website
        $websites = DB::get_websites();

        foreach ($websites as $website) {
        	// process only those that are already tested
        	if(intval($website->tested) === 0)
        		continue;

            $connector = new Connector($website->id);
            
            $connector->get_remote_cats();
            // this just set the value of the categories novrmalized to the connector
            $connector->set_main_post_categories($categories_normalized);
            $connector->maybe_create_remote_cats();
            
            $data_post['categories'] = $connector->normalize_categories();
            // the post replication is based on categories allowed, if no one, then just continue
            if(count($data_post['categories']) == 0)
                continue;
            
            if($local_data = DB::get_post_by_local($connector->get_id(), $post_id)){
                $post_response = $connector->update_remote_post($connector->get_id(), $data_post, $local_data);
            } else {
                $post_response = $connector->create_remote_post($connector->get_id(), $data_post, $post_id);
            }

            if(!$post_response)
                continue;
            
            if(!$local_data)
                $local_data = DB::get_post_by_local($connector->get_id(), $post_id);
            
        	$thumb = get_the_post_thumbnail_url($post_id, 'full');
            
        	if(!$thumb)
        		continue;

            $thumb_remote = $connector->update_local_media($thumb, $local_data);

            if(!$thumb_remote){
                continue;
            }
            
        	$post_thumb_response = $connector->set_remote_post_media($post_response->id, $thumb, $thumb_remote);

        	// done, the post content and the thumb are already sended
        }
	}

}
