<?php
namespace PostReplicator;

class Helpers
{
	public static function get_categories($post_id)
	{
		// get the categories of the current post
        $categories_raw = get_the_category($post_id);
        $categories_normalized = array();
        foreach ($categories_raw as $cat) {
        	$categories_normalized[$cat->slug] = array(
        		'term_id' => $cat->term_id,
        		'name' => $cat->name
        	);
        }

        return $categories_normalized;
	}
}