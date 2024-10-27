<div class="wrap">
    <h2><?php echo $this->plugin->displayName; ?> &raquo; <?php esc_html_e('Settings', 'ad-base-modal');?></h2>

    <?php
if (isset($this->message)) {
    ?>
        <div class="updated fade"><p><?php echo $this->message; ?></p></div>
        <?php
}
if (isset($this->errorMessage)) {
    ?>
        <div class="error fade"><p><?php echo $this->errorMessage; ?></p></div>
        <?php
}
?>

<?php
// Which tab
$default_tab = null;
$tab_options = ['form', 'posts'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
// Ensure we're showing the right tab.
if (!in_array($tab, $tab_options)) {
    $tab = $default_tab;
}

?>

    <div id="poststuff">
    	<div id="post-body" class="metabox-holder columns-2">

		<nav class="nav-tab-wrapper">
		<a href="?page=ad-base-wordpress-modal" class="nav-tab <?php if ($tab == null): ?>nav-tab-active<?php endif;?>">Token & Default Popup</a>
		<a href="?page=ad-base-wordpress-modal&tab=form" class="nav-tab <?php if ($tab === 'form'): ?>nav-tab-active<?php endif;?>">Lightweight Subscribe Form</a>
		<a href="?page=ad-base-wordpress-modal&tab=posts" class="nav-tab <?php if ($tab === 'posts'): ?>nav-tab-active<?php endif;?>">Posts Mailer</a>
		</nav>

			<?php if ($tab == null): ?>
    		<!-- Content -->
    		<div id="post-body-content" dataid>
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
	                <div class="postbox">
	                    <div class="inside">
		                    <form action="options-general.php?page=<?php echo $this->plugin->name; ?>" method="post">
		                    	<p>
		                    		<label for="adbm_popup_token"><strong><?php esc_html_e('SendSquared Token', 'ad-base-modal');?></strong></label>
									<div style="padding-left: 20px;">
		                    		<input name="adbm_popup_token" id="adbm_popup_token" class="widefat" type="text" value="<?php echo $this->settings['adbm_popup_token']; ?>" />
		                    		<?php esc_html_e('This can be found in your SendSquared account section under [Integrations].', 'ad-base-modal');?>
									</div>
		                    	</p>
								<?php if (strlen($this->settings['adbm_popup_token']) > 0): ?>
		                    	<p>
		                    		<label for="adbm_popup_id"><strong><?php esc_html_e('Popup ID', 'ad-base-modal');?></strong></label>
									<br>
									<div style="padding-left: 20px;">
									<select name="adbm_popup_id" id="adbm_popup_id">
									<?php
// get list of template
foreach ($this->settings['adbm_posts_templates'] as $template):
    if ($template->type == 'popup'):
    ?>
																															<option value="<?php echo $template->id; ?>"<?php echo (isset($this->settings['adbm_popup_id']) && $this->settings['adbm_popup_id'] == $template->id) ? ' selected' : ''; ?>><?php echo $template->name; ?></option>
																															<?php
endif;
endforeach;
?>
									</select>
		                    		<!-- <input name="adbm_popup_id" id="adbm_popup_id" class="widefat" type="number" value="<?php echo $this->settings['adbm_popup_id']; ?>" /> -->
									<br>
		                    		<?php esc_html_e('This should be the number associated with the Popup you\'ve designed in SendSquared.', 'ad-base-modal');?>
									</div>
		                    	</p>
								<?php endif;?>
		                    	<?php wp_nonce_field($this->plugin->name, $this->plugin->name . '_nonce');?>
		                    	<p>
									<input name="submit" type="submit" name="Submit" class="button button-primary" value="<?php esc_html_e('Save', 'ad-base-modal');?>" />
								</p>
						    </form>
	                    </div>
	                </div>
	                <!-- /postbox -->
				</div>
				<!-- /normal-sortables -->
    		</div>
			<!-- /post-body-content -->
			<?php endif;?>


    		<!-- Sidebar -->
    		<div id="postbox-container-1" class="postbox-container">
    			<?php require_once $this->plugin->folder . '/views/sidebar.php';?>
    		</div>
    		<!-- /postbox-container -->


			<?php if ($tab === 'posts'): ?>
			<!-- Content -->
			<div id="post-body-content" dataid>
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
	                <div class="postbox">
	                    <div class="inside">
							<form action="options-general.php?page=<?php echo $this->plugin->name; ?>" method="post">
								<p>
									This feature when enabled will allow any new posts to be scheduled to go out to the subset listed groups or segments.
								</p>
		                    	<p>
									<label for="adbm_posts_campaign"><strong><?php esc_html_e('Email New Posts:', 'ad-base-modal');?></strong></label>
									<br>
									<div style="padding-left: 20px;">
			<input name="adbm_posts_campaign" id="adbm_posts_campaign" class="widefat" type="checkbox" <?php
if (isset($this->settings['adbm_posts_campaign']) && $this->settings['adbm_posts_campaign'] == 'true'):
?> checked="checked"<?php
endif;
?> /> Email contacts new posts.
									</div>
								</p>
								<?php
// So only show these options once it's toggled on.
if (isset($this->settings['adbm_posts_campaign']) && $this->settings['adbm_posts_campaign'] == 'true'):
?>
								<p>
									<label for="adbm_posts_smart_send"><strong><?php esc_html_e('Use Smart Send:', 'ad-base-modal');?></strong></label>
									<br>
									<div style="padding-left: 20px;">
			<input name="adbm_posts_smart_send" id="adbm_posts_smart_send" class="widefat" type="checkbox" <?php
if (isset($this->settings['adbm_posts_smart_send']) && $this->settings['adbm_posts_smart_send'] == 'true'):
?> checked="checked"<?php
endif;
?> /> Smart send will find the best time to send your post to each of your contacts
									</div>
								</p>

								<p>
									<label for="adbm_posts_template"><strong><?php esc_html_e('Template:', 'ad-base-modal');?></strong></label>
									<br>
									<div style="padding-left: 20px;">
									<select name="adbm_posts_template" id="adbm_posts_template">
									<?php
// get list of template
foreach ($this->settings['adbm_posts_templates'] as $template):
    if ($template->type == 'email'):
    ?>
																															<option value="<?php echo $template->id; ?>"<?php echo (isset($this->settings['adbm_posts_template']) && $this->settings['adbm_posts_template'] == $template->id) ? ' selected' : ''; ?>><?php echo $template->name; ?></option>
																															<?php
endif;
endforeach;
?>
									</select>
									<br>
									Use the table below labeled "Template Tokens" to include tokens in your SendSquared templates.
									</div>
								</p>

								<p>
									<label><strong><?php esc_html_e('Groups:', 'ad-base-modal');?></strong></label>
									<br>
									<div style="padding-left: 20px;">
									<?php
// get list of groups
foreach ($this->settings['adbm_posts_groups'] as $group):
?>
									<input name="adbm_posts_selected_group_<?php echo $group->id; ?>" id="adbm_posts_selected_group_<?php echo $group->id; ?>" class="widefat" type="checkbox" <?php
if (isset($this->settings['adbm_posts_selected_group_' . $group->id]) && $this->settings['adbm_posts_selected_group_' . $group->id] == 'true'):
?> checked="checked"<?php
endif;
?> /> <?php echo $group->name; ?> <br/>
									<?php
endforeach;
?>
									</div>
								</p>
								<?php
endif;
?>

		                    	<?php wp_nonce_field($this->plugin->name, $this->plugin->name . '_new_post_nonce');?>
		                    	<p style="padding-top:50px;">
									<input name="submit-new-posts" type="submit" name="Submit" class="button button-primary" value="<?php esc_html_e('Save', 'ad-base-modal');?>" />
								</p>
								<br>
								<p>Big thanks to <a href="https://webbiquity.com/?ref=send-squared-wp-plugin">Tom Pick</a> over at Webbiquity!</p>
						    </form>
	                    </div>
	                </div>
	                <!-- /postbox -->
				</div>
				<!-- /normal-sortables -->
    		</div>
			<!-- /post-body-content -->
			<?php endif;?>

			<?php if ($tab === 'posts'): ?>
			<div id="post-body-content" dataid>
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
	                <div class="postbox">
	                    <div class="inside">
						<p>
							<b>Template Tokens</b>
						</p>
						<table>
							<tr><td>%POST-TITLE%</td><td>The post Title</td></tr>
							<tr><td>%POST-URL%</td><td>The full URL to your post</td></tr>
							<tr><td>%POST-PREVIEW%</td><td>The preview text</td></tr>
							<tr><td>%POST-EXCERPT%</td><td>The post excerpt</td></tr>
							<tr><td>%POST-FEATURED-IMAGE%</td><td>The post featured image</td></tr>
							<tr><td>%POST-DATE%</td><td>The post date</td></tr>
						</table>
						</div>
	                <!-- /postbox -->
				</div>
				<!-- /normal-sortables -->
    		</div>
			<!-- /post-body-content -->
			<?php endif;?>

			<?php if ($tab === 'form'): ?>
			<!-- Content -->
    		<div id="post-body-content">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
	                <div class="postbox">
	                    <div class="inside">
		                    <form action="options-general.php?page=<?php echo $this->plugin->name; ?>" method="post">
		                    	<p>
		                    		<label for="adbm_group_token"><strong><?php esc_html_e('SendSquared Group Token', 'ad-base-modal');?></strong></label>
		                    		<input name="adbm_group_token" id="adbm_group_token" class="widefat" type="text" value="<?php echo $this->settings['adbm_group_token']; ?>" />
		                    		<?php esc_html_e('This can be found in your SendSquared account section under [Integrations].', 'ad-base-modal');?>
		                    	</p>
		                    	<p>
		                    		<label for="adbm_form_title"><strong><?php esc_html_e('Contact Form Title', 'ad-base-modal');?></strong></label>
		                    		<input name="adbm_form_title" id="adbm_form_title" class="widefat" type="text" value="<?php echo $this->settings['adbm_form_title']; ?>" />
		                    		<?php esc_html_e('This is the title that will appear just above the form.', 'ad-base-modal');?>
								</p>
								<p>
		                    		<label><strong><?php esc_html_e('Email or Phone Number, or Both', 'ad-base-modal');?></strong></label>
									<br>
									<blockquote>
										<label for="adbm_form_input_type_email">
											<input
												name="adbm_form_input_type"
												id="adbm_form_input_type_email"
												type="radio"
												value="email"
												<?php echo $this->settings['adbm_form_input_type'] == 'email' ? ' checked="checked" ' : ''; ?>
												/> Email
										</label>
										<br>
										<label for="adbm_form_input_type_phone">
											<input
												name="adbm_form_input_type"
												id="adbm_form_input_type_phone"
												type="radio"
												value="phone"
												<?php echo $this->settings['adbm_form_input_type'] == 'phone' ? ' checked="checked" ' : ''; ?>
												/> Phone
										</label>
										<br>
										<label for="adbm_form_input_type_both">
											<input
												name="adbm_form_input_type"
												id="adbm_form_input_type_both"
												type="radio"
												value="both"
												<?php echo $this->settings['adbm_form_input_type'] == 'both' ? ' checked="checked" ' : ''; ?>
												/> Both
										</label>
									</blockquote>
								</p>
								<p>
		                    		<label ><strong><?php esc_html_e('Additional Fields', 'ad-base-modal');?></strong></label>
									<br>
									<blockquote>
										<label for="adbm_form_input_name">
											<input
												name="adbm_form_input_name"
												id="adbm_form_input_name"
												type="checkbox"
												value="email"
												<?php echo $this->settings['adbm_form_input_name'] == 'true' ? ' checked="checked" ' : ''; ?>
												/> Name
										</label>
										<br>
										<label for="adbm_form_input_gender">
											<input
												name="adbm_form_input_gender"
												id="adbm_form_input_type_gender"
												type="checkbox"
												value="true"
												<?php echo $this->settings['adbm_form_input_gender'] == 'true' ? ' checked="checked" ' : ''; ?>
												/> Gender
										</label>
										<br>
										<label for="adbm_form_title">
											<input
												name="adbm_form_input_zip_code"
												id="adbm_form_input_zip_code"
												type="checkbox"
												value="true"
												<?php echo $this->settings['adbm_form_input_zip_code'] == 'true' ? ' checked="checked" ' : ''; ?>
												/> Zip Code
										</label>
									</blockquote>
		                    	</p>
								<p>
		                    		<label><strong><?php esc_html_e('Additional Styles / Classes', 'ad-base-modal');?></strong></label>
									<br>
									<label for="adbm_form_input_class">
										Custom Input Class(es)
									</label>
									<input
										name="adbm_form_input_class"
										id="adbm_form_input_class"
										type="text"
										class="widefat"
										value="<?php echo isset($this->settings['adbm_form_input_class']) ? $this->settings['adbm_form_input_class'] : ''; ?>"
										/>
									<?php esc_html_e('example: [.custom-input .custom-primary-input]', 'ad-base-modal');?>
									<br>
									<br>
									<label for="adbm_form_input_style">
										Custom Input Style(s)
									</label>
									<input
										name="adbm_form_input_style"
										id="adbm_form_input_style"
										type="text"
										class="widefat"
										pattern="^([a-zA-Z\-\s]+:[a-zA-Z0-9\-\s#%\(\)\+']*;)+$"
										value="<?php echo isset($this->settings['adbm_form_input_style']) ? $this->settings['adbm_form_input_style'] : ''; ?>"
										/>
									<?php esc_html_e('example: [border:2px; font-color: \'gray\';]', 'ad-base-modal');?>
									<br>
									<br>
									<label for="adbm_form_button_class">
										Custom Button Class(es)
									</label>
									<input
										name="adbm_form_button_class"
										id="adbm_form_button_class"
										type="text"
										class="widefat"
										pattern="^(\w+)?(\s*>\s*)?(#\w+)?\s*(\.\w+)?\s*"
										value="<?php echo isset($this->settings['adbm_form_button_class']) ? $this->settings['adbm_form_button_class'] : ''; ?>"
										/>
									<?php esc_html_e('example: [.custom-btn .custom-primary-btn]', 'ad-base-modal');?>
									<br>
									<br>
									<label for="adbm_form_button_style">
										Custom Button Style(s)
									</label>
									<input
										name="adbm_form_button_style"
										id="adbm_form_button_style"
										type="text"
										class="widefat"
										pattern="^([a-zA-Z\-\s]+:[a-zA-Z0-9\-\s#%\(\)\+']*;)+$"
										value="<?php echo isset($this->settings['adbm_form_button_style']) ? $this->settings['adbm_form_button_style'] : ''; ?>"
										/>
									<?php esc_html_e('example: [border:2px; font-color: \'gray\';]', 'ad-base-modal');?>
									<br>
		                    	</p>
		                    	<?php wp_nonce_field($this->plugin->name, $this->plugin->name . '_inlay_nonce');?>
		                    	<p style="margin-top: 25px;">
									<input name="submit-inlay" type="submit" name="Submit" class="button button-primary" value="<?php esc_html_e('Save', 'ad-base-modal');?>" />
								</p>
						    </form>
	                    </div>
	                </div>
	                <!-- /postbox -->
				</div>
				<!-- /normal-sortables -->
    		</div>
    		<!-- /post-body-content -->
			<?php endif;?>

    	</div>
	</div>
</div>
