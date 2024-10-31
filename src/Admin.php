<?php
namespace PostReplicator;

class Admin
{
	public function register_admin_menu()
	{
		add_menu_page(
            'Post Replicator', 
            'Post Replicator',
            'manage_options',
            'post-replicator',
            array($this, 'general_callback')
        );

        add_submenu_page(
        	'post-replicator',
            'General', 
            'General',
            'manage_options',
            'post-replicator',
            array($this, 'general_callback')
        );

        add_submenu_page(
        	'post-replicator',
            'Add Website', 
            'Add Website',
            'manage_options',
            'post-replicator-add-website',
            array($this, 'add_website_callback')
        );
	}

	public function general_callback()
	{
		if(Route::is_config_website()):
			if($website = DB::get_website(Route::get_config_website())):
				$cats = array();
				foreach (get_categories() as $cat) {
					$cats[$cat->slug] = array(
						'term_id' => $cat->term_id,
						'name' => $cat->name
					);
				}
				$cats_config = unserialize($website->categories_config);
				?>
				<div class="wrap">
					<h2>Categories configuration for the website: <?php echo esc_html($website->alias); ?></h2>
					<form method="post" action="<?php echo esc_html(get_admin_url().'admin.php?page=post-replicator&update_cats=true'); ?>">
						<input type="hidden" name="postreplicator_website" value="<?php echo esc_html($website->id); ?>">
						<table class="form-table">
							<tbody>
								<?php foreach ($cats as $slug => $values): ?>
								<tr>
									<td style="width: 180px;"><?php echo esc_html($values['name']); ?></td>
									<td>
										<select name="postreplicator_cats[<?php echo esc_html($slug); ?>]">
											<option value="no" <?php if(isset($cats_config[$slug]) && $cats_config[$slug] == 'no'){ echo 'selected="selected"';} ?>>Deshabilitada</option>
											<option value="yes" <?php if(isset($cats_config[$slug]) && $cats_config[$slug] == 'yes'){ echo 'selected="selected"';} ?>>Habilitada</option>
										</select>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<button type="submit" class="button">Save</button>
					</form>
				</div>
				<?php
			endif;
		else:
		$websites = DB::get_websites();
		?>
		<div class="wrap">
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<table class="wp-list-table widefat fixed striped table-view-list posts">
				<thead>
					<tr>
						<td>Alias</td>
						<td>Website URL</td>
						<td>Username</td>
						<td>Date</td>
						<td>Actions</td>
					</tr>
				</thead>
				<tbody>
				<?php 
				if($websites):
					foreach ($websites as $website): 
					?>
					<tr>
						<td>
							<?php echo esc_html($website->alias); ?> 
							<?php if(intval($website->tested) === 1): ?>
							<span style="color: #1f8318; font-weight: 600;">(Enabled)</span>
							<?php elseif(intval($website->tested) === 0): ?>
							<span style="color: #e12323; font-weight: 600;">(Disabled)</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html($website->url); ?></td>
						<td><?php echo esc_html($website->username); ?></td>
						<td><?php echo esc_html($website->created_at); ?></td>
						<td>
							<form method="post" style="display: inline-block;">
								<input type="hidden" name="postreplicator_action" value="test">
								<input type="hidden" name="postreplicator_website" value="<?php echo esc_html($website->id); ?>">
								<button class="button">Test</button>
							</form>
							<a class="button" href="<?php echo get_admin_url().'admin.php?page=post-replicator-add-website&edit=true&website='.esc_html($website->id); ?>" class="button">Edit</a>
							<a class="button" href="<?php echo get_admin_url().'admin.php?page=post-replicator&config=true&website='.esc_html($website->id); ?>" class="button">Config</a>
							<form method="post" style="display: inline-block;">
								<input type="hidden" name="postreplicator_action" value="delete">
								<input type="hidden" name="postreplicator_website" value="<?php echo esc_html($website->id); ?>">
								<button class="button">Delete</button>
							</form>
						</td>
					</tr>
					<?php 
					endforeach; 
				endif;
				?>
				</tbody>
			</table>
		</div>
		<?php
		endif;
	}

	public function add_website_callback()
	{
		if(Route::is_edit_website()):
			$website_id = Route::get_edit_website();
			if($website_id && $website = DB::get_website($website_id)):
			?>
			<div class="wrap">
				<h2>Edit Website</h2>
				<form method="post">
					<input type="hidden" name="post_replicator_id" value="<?php echo esc_html($website->id); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<td style="width: 200px;">Website Alias</td>
								<td><input type="text" name="post_replicator_alias" value="<?php echo esc_html($website->alias); ?>"></td>
							</tr>
							<tr>
								<td style="width: 200px;">Website URL</td>
								<td><input type="text" name="post_replicator_url" value="<?php echo esc_html($website->url); ?>"></td>
							</tr>
							<tr>
								<td style="width: 200px;">Username</td>
								<td><input type="text" name="post_replicator_username" value="<?php echo esc_html($website->username); ?>"></td>
							</tr>
							<tr>
								<td style="width: 200px;">Application Password</td>
								<td><input type="text" name="post_replicator_password"></td>
							</tr>
						</tbody>
					</table>
					<div style="margin-left: 5px; margin-top: 15px;">
						<button type="submit" class="button button-primary">Actualizar</button>	
					</div>
				</form>
			</div>
			<?php 
			endif;
		else: 
		?>
		<div class="wrap">
			<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
			<form method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<td style="width: 200px;">Website Alias</td>
							<td><input type="text" name="post_replicator_alias"></td>
						</tr>
						<tr>
							<td style="width: 200px;">Website URL</td>
							<td><input type="text" name="post_replicator_url"></td>
						</tr>
						<tr>
							<td style="width: 200px;">Username</td>
							<td><input type="text" name="post_replicator_username"></td>
						</tr>
						<tr>
							<td style="width: 200px;">Application Password</td>
							<td><input type="text" name="post_replicator_password"></td>
						</tr>
					</tbody>
				</table>
				<div style="margin-left: 5px; margin-top: 15px;">
					<button type="submit" class="button button-primary">Agregar</button>	
				</div>
			</form>
		</div>
		<?php
		endif;
	}
}