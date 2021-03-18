<?php 
if(isset($_GET['eblc_tab'])) {
	$eblc_global_url_tab = $_GET['eblc_tab'];
} else {
	$eblc_global_url_tab = '';
}

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $eblc_global_url = "https"; 
else
    $eblc_global_url = "http"; 
	  
// Here append the common URL characters. 
$eblc_global_url .= "://"; 
  
// Append the host(domain name, ip) to the URL. 
$eblc_global_url .= $_SERVER['HTTP_HOST']; 
  
// Append the requested resource location to the URL 
$eblc_global_url .= $_SERVER['REQUEST_URI'];

while ( substr( $eblc_global_url, -1, 1 ) == '/' ) {
	$eblc_global_url = substr( $eblc_global_url, 0, -1 );
	if( substr( $eblc_global_url, -1, 1 ) != '/' ) {
		break;
	}
}

$eblc_global_url_array = explode("&", $eblc_global_url);
$eblc_global_url = $eblc_global_url_array[0];
    

?>
<!-- Checker option page view -->
<input type="hidden" id="eblc_global_url_tab" value="<?php echo $eblc_global_url_tab;?>" />
<input type="hidden" id="eblc_global_url" value="<?php echo $eblc_global_url;?>" />
<div class="eblc_check_options col-md-12 row" style="padding: 20px 20px 0px 4px; ">
	<h4 class="eblc-page-title col-md-12"> Link Manager Options </h4>
	<div class="col-md-10">	    
		<ul class="nav nav-tabs">
		  	<li class="nav-item" tab_name="eblc_general"><a class="nav-link <?php if($eblc_global_url_tab == '' || $eblc_global_url_tab == 'eblc_general' ) { echo 'active';} else { echo '';} ?>" data-toggle="tab" href="#eblc_general"> Broken Links </a></li>
		  	<li class="nav-item" tab_name="eblc_auto_linking"><a class="nav-link <?php if( $eblc_global_url_tab == 'eblc_auto_linking' ) { echo 'active';} else { echo '';} ?>" data-toggle="tab" href="#eblc_auto_linking"> Auto Linking </a></li>
		  	<li class="nav-item" tab_name="eblc_url_shortener"><a class="nav-link <?php if( $eblc_global_url_tab == 'eblc_url_shortener' ) { echo 'active';} else { echo '';} ?>" data-toggle="tab" href="#eblc_url_shortener"> URL Shortener </a></li>
		  	<li class="nav-item" tab_name="eblc_redirection"><a class="nav-link <?php if( $eblc_global_url_tab == 'eblc_redirection' ) { echo 'active';} else { echo '';} ?>" data-toggle="tab" href="#eblc_redirection"> Redirection </a></li>
		  	<li class="nav-item" tab_name="eblc_security_others"><a class="nav-link <?php if( $eblc_global_url_tab == 'eblc_security_others' ) { echo 'active';} else { echo '';} ?>" data-toggle="tab" href="#eblc_security_others"> Security And Others </a></li>
		</ul>

		<div class="tab-content eblc_options_contents">

			<!-- General Options -->
		  	<div id="eblc_general" class="tab-pane <?php if($eblc_global_url_tab == '' || $eblc_global_url_tab == 'eblc_general' ) { echo 'active';} else { echo 'fade';} ?>">
		  		<form method="post" action="options.php" class="eblc-options-form">

					<?php 
						settings_fields( 'eblc_options' );
				        do_settings_sections( 'eblc_options' );
				        if (get_option( 'eblc_options' ) != '' ) {
				        	$eblc_options = get_option( 'eblc_options' );
				        } else {
				        	$eblc_options = $eblc_default_options;
				        }
				    ?>
			  		<table class="eblc_form_table">
			  			<tr valign="top">
					        <th scope="row"> Checking status </th>
					        <td>
								<div id="eblc_full_status">
									<?php
										$internal_external_broken_links = eblc_get_internal_external_broken_links();
										$internal_broken_links = $internal_external_broken_links['internal_broken_links'];
										$external_broken_links = $internal_external_broken_links['external_broken_links'];

										$total_links = eblc_get_total_links();

										if ( $internal_broken_links == 0 && $external_broken_links == 0 ) {
											echo 'No internal or external broken links found. <br>';
										} else {
											if ( $internal_broken_links != 0 ) {
												if ( $internal_broken_links == 1 ) {
													echo 'Detected ' . $internal_broken_links . ' internal broken link found. <br>';
												} else {
													echo 'Detected ' . $internal_broken_links . ' internal broken links found. <br>';
												}											
											} else {
												echo 'No internal broken links found. <br>';
											}

											if ( $external_broken_links != 0 ) {
												if ( $external_broken_links == 1 ) {
													echo 'Detected ' . $external_broken_links . ' external broken link found. <br>';
												} else {
													echo 'Detected ' . $external_broken_links . ' external broken links found. <br>';
												}											
											} else {
												echo 'No external broken links found. <br>';
											}
										}

										if ( $total_links == 0 ) {
											echo 'No links Detected.';
										} else {
											echo 'Detected ' . $total_links . ' links.';
										}
									?>
								</div>
					        </td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Check links </th>
					        <td>					        	
								Every 
								<input type="text" name="eblc_options[check_threshold]" id="eblc_check_threshold" value="<?php echo $eblc_options['check_threshold'];?>" size="5" maxlength="5"/> hours <br>
						        <span class="description"> Check existing links every this value. </span><br><br>
						        <span class="btn button-primary" id="eblc_instant_check">Check Broken Links</span> <span id="eblc_instant_check_spinner" class="spinner-border text-info" style="width: 1.2em; height: 1.2em; display: none;"></span><br><br>
					        </td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Email notification setting </th>
					        <td>
					        	<p>
					        		<label for="eblc_send_email_notifications_control">
						        		<input type="hidden" name="eblc_options[send_email_notifications_control]" value="0" >
						        		<input 
						        			type  = "checkbox" data-toggle="toggle" data-onstyle="success" data-height="30"
						        			name  = "eblc_options[send_email_notifications_control]" 
						        			id    = "eblc_send_email_notifications_control" 
						        			value = "<?php echo $eblc_options['send_email_notifications_control'] ? true : false; ?>"
						        			<?php echo $eblc_options['send_email_notifications_control'] ? "checked='checked'" : ''; ?> /> <br>
						        	</label>
					        	</p>
					        	<p>
						        	<label for="eblc_send_email_notifications">
						        		<input type="hidden" name="eblc_options[send_email_notifications]" value="<?php echo $eblc_options['send_email_notifications'] ? true : false; ?>" >
						        		<input 
						        			type  = "checkbox" 
						        			name  = "eblc_options[send_email_notifications]" 
						        			id    = "eblc_send_email_notifications" 
						        			value = "<?php echo $eblc_options['send_email_notifications'] ? true : false; ?>"
						        			<?php echo $eblc_options['send_email_notifications'] ? "checked='checked'" : ''; ?> >
						            	Send notification to administrator email about newly detected broken links.
						            </label><br>
								</p>
						        <p>
						        	<label for="eblc_send_authors_email_notifications">
						        		<input type="hidden" name="eblc_options[send_authors_email_notifications]" value="<?php echo $eblc_options['send_authors_email_notifications'] ? true : false; ?>" >
						        		<input 
						        			type  = "checkbox" 
						        			name  = "eblc_options[send_authors_email_notifications]" 
						        			id    = "eblc_send_authors_email_notifications" 
						        			value = "<?php echo $eblc_options['send_authors_email_notifications'] ? true : false; ?>"
						        			<?php echo $eblc_options['send_authors_email_notifications'] ? "checked='checked'" : ''; ?> >
						            	Send notification to author email about broken links in their posts.	
						            </label><br>
								</p>
					        </td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Email address to send notification </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_options[notification_email_address]" id="notification_email_address" value="<?php echo $eblc_options['notification_email_address'];?>" class="regular-text ltr">
									</label><br>
									<span class="description"> If it is empty, it uses the setting default email address. </span>
								</p>
							</td>
					    </tr>
			  		</table>
			  		<?php  submit_button(); ?>
			  	</form>
		  	</div>

		  	<!-- Auto Linking Options -->
		  	<div id="eblc_auto_linking" class="tab-pane <?php if( $eblc_global_url_tab == 'eblc_auto_linking' ) { echo 'active';} else { echo 'fade';} ?>">
		  		<form method="post" action="options.php" class="eblc-options-form">

					<?php 
						settings_fields( 'eblc_link_options' );
				        do_settings_sections( 'eblc_link_options' );
				        if (get_option( 'eblc_link_options' ) != '' ) {
				        	$eblc_link_options = get_option( 'eblc_link_options' );
				        } else {
				        	$eblc_link_options = $eblc_default_link_options;
				        }
				    ?>
			  		<table class="eblc_form_table">

					    <tr valign="top">
					        <th scope="row"> Set All Internal Link </th>
					        <td>
					        	<p style="margin-top: 0;">
						        	<label for="eblc_internal_link_follow_setting">
						        		<input 
						        			type="radio" 
						        			name="eblc_link_options[internal_link_follow_setting]" 
						        			value="dofollow" 
						        			class="internal_link_follow_setting_dofollow" 
						        			<?php echo $eblc_link_options['internal_link_follow_setting'] == 'dofollow' ? "checked" : ''; ?> /> dofollow 
                                		<input 
                                			style="margin-left: 10px;"
                                			type="radio"
                                			name="eblc_link_options[internal_link_follow_setting]" 
                                			value="nofollow" 
                                			class="internal_link_follow_setting_nofollow" 
                                			<?php echo $eblc_link_options['internal_link_follow_setting'] == 'nofollow' ? "checked" : ''; ?> /> nofollow
						            </label>
						            <label for="eblc_internal_link_target_setting">
						        		<input type="hidden" name="eblc_link_options[internal_link_target_setting]" value="<?php echo $eblc_link_options['internal_link_target_setting'] ? true : false; ?>" >
						        		<input 
						        			style="margin-left: 10px;"
						        			type  = "checkbox" 
						        			name  = "eblc_link_options[internal_link_target_setting]" 
						        			id    = "eblc_internal_link_target_setting" 
						        			value = "<?php echo $eblc_link_options['internal_link_target_setting'] ? true : false; ?>"
						        			<?php echo $eblc_link_options['internal_link_target_setting'] ? "checked='checked'" : ''; ?> >
						            	Open In New Window
						            </label>
						            <br>
								</p>
					        </td>
					    </tr>

					    <tr valign="top">
					        <th scope="row"> Set All External Link </th>
					        <td>
					        	<p style="margin-top: 0;">
						        	<label for="eblc_external_link_follow_setting">
						        		<input 
						        			type="radio" 
						        			name="eblc_link_options[external_link_follow_setting]" 
						        			value="dofollow" 
						        			class="external_link_follow_setting_dofollow" 
						        			<?php echo $eblc_link_options['external_link_follow_setting'] == 'dofollow' ? "checked" : ''; ?> /> dofollow 
                                		<input 
                                			style="margin-left: 10px;"
                                			type="radio"
                                			name="eblc_link_options[external_link_follow_setting]" 
                                			value="nofollow" 
                                			class="external_link_follow_setting_nofollow" 
                                			<?php echo $eblc_link_options['external_link_follow_setting'] == 'nofollow' ? "checked" : ''; ?> /> nofollow
						            </label>
						            <label for="eblc_external_link_target_setting">
						        		<input type="hidden" name="eblc_link_options[external_link_target_setting]" value="<?php echo $eblc_link_options['external_link_target_setting'] ? true : false; ?>" >
						        		<input 
						        			style="margin-left: 10px;"
						        			type  = "checkbox" 
						        			name  = "eblc_link_options[external_link_target_setting]" 
						        			id    = "eblc_external_link_target_setting" 
						        			value = "<?php echo $eblc_link_options['external_link_target_setting'] ? true : false; ?>"
						        			<?php echo $eblc_link_options['external_link_target_setting'] ? "checked='checked'" : ''; ?> >
						            	Open In New Window
						            </label>
								</p>
					        </td>
					    </tr>
			  		</table>
			  		<?php  submit_button(); ?>
			  	</form>

			  	<div class="eblc_auto_linking_option_section">
			  		<table class="eblc_auto_linking_option">
			  			<tr valign="top">
					        <th scope="row"> Auto Linking </th>
					        <td>
					        	<p style="margin-top: 0;">
					        		Anchor Text 
					        		<input 
					        			style="width: 300px; margin-left: 10px;"
					        			id="eblc_auto_linking_anchor_text" 
					        			type="text" 
					        			name="auto_linking_anchor_text"					        			 
					        			placeholder="Keyword 01,Keyword02,Keyword03" />
								</p>
								<p style="margin-top: 0;">
					        		Linking URL  
					        		<input 
					        			style="width: 300px; margin-left: 10px;"
					        			id="eblc_auto_linking_url" 
					        			type="text" 
					        			name="auto_linking_url" 					        			
					        			placeholder="Target URL" />
						            <br>
								</p>
								<p>
									<button class="btn button-primary eblc_auto_linking_create_btn">Create Now</button> <span id="eblc_create_autolinking_spinner" class="spinner-border text-info" style="width: 1.2em; height: 1.2em; display: none;"></span>
								</p>
					        </td>
					    </tr>
			  		</table>

			  	</div>
		  	</div>

		  	<!-- URL Shortener Options -->
		  	<div id="eblc_url_shortener" class="tab-pane <?php if( $eblc_global_url_tab == 'eblc_url_shortener' ) { echo 'active';} else { echo 'fade';} ?>">
		  		<div>
			    	<table class="eblc_form_table">
				        <tr valign="top">
					        <th scope="row"> Paste Your URL Here: </th>
					        <td>
								<p>
									<label>
										<input type="text" id="eblc_shorten_target_url" class="regular-text ltr" placeholder="Affiliate or any other links" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Short URL: </th>
					        <td>
								<p>
									<label>
										<?php echo home_url(); ?>/<input type="text" id="eblc_shorten_url" class="regular-text ltr" placeholder="best-url-shortener" style="width: 18.7em;" />
									</label>
								</p>
							</td>
					    </tr>
					</table>
					<p class="submit">
						<input type="button" id="eblc_shorten_url_btn" class="button button-primary" value="Save and Create"/>
					</p>
				</div>
		  	</div>

		  	<!-- URL Redirection Options -->
		  	<div id="eblc_redirection" class="tab-pane <?php if( $eblc_global_url_tab == 'eblc_redirection' ) { echo 'active';} else { echo 'fade';} ?>">
		  		<h5 style="padding-top: 20px;">Add New Redirection</h5><br>
		    	<div>
			    	<table class="eblc_form_table">
				        <tr valign="top">
					        <th scope="row"> Source URL: </th>
					        <td>
								<p>
									<label>
										<input type="text" id="eblc_redirection_source_url" class="regular-text ltr" placeholder="The URL you want to redirect from" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Target URL: </th>
					        <td>
								<p>
									<label>
										<input type="text" id="eblc_redirection_target_url" class="regular-text ltr" placeholder="The Target URL you want to redirect" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row">
					        	<label for="eblc-internal-link-settings-rel_follow">
					        		Redirections Type: 
					        	</label>
					        </th>
					        <td>
								<p>
									<label>
										<select id="eblc_redirection_type">
							               	<option value="301">
							                    301 Redirect
							               	</option>
							               	<option value="302">
							                    302 Redirect
							               	</option>
							               	<option value="307">
							                    307 Redirect
							               	</option>
							           	</select>
									</label>
								</p>
							</td>
					    </tr>
					</table>
					<p class="submit">
						<input type="button" id="eblc_redirection_url_btn" class="button button-primary" value="Save Changes"/>
					</p>
				</div>					
		  	</div>

		 	<!-- Advanced Options -->
		 	<div id="eblc_security_others" class="tab-pane <?php if( $eblc_global_url_tab == 'eblc_security_others' ) { echo 'active';} else { echo 'fade';} ?>">
		 		<h5 style="padding-top: 20px;">Security</h5><br>
		    	<form method="post" action="options.php" class="eblc-sec-options-form">
		    		<?php 
						settings_fields( 'eblc_sec_options' );
				        do_settings_sections( 'eblc_sec_options' );
				        if (get_option( 'eblc_sec_options' ) != '' ) {
				        	$eblc_sec_options = get_option( 'eblc_sec_options' );
				        } else {
				        	$eblc_sec_options = $eblc_default_sec_options;
				        }
				    ?>
			    	<table class="eblc_form_table">
				        <tr valign="top">
					        <th scope="row"> Login Attempt Limit Number: </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_sec_options[login_attempt_limit_num]" id="eblc_login_attempt_limit_num" value="<?php echo $eblc_sec_options['login_attempt_limit_num'];?>" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Lockout Time: </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_sec_options[lockout_time]" id="eblc_lockout_time" value="<?php echo $eblc_sec_options['lockout_time'];?>" /> hours
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Notify On Lockout Mail: </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_sec_options[lockout_notify_mail]" id="eblc_lockout_notify_mail" class="regular-text ltr" placeholder="test@example.com" value="<?php echo $eblc_sec_options['lockout_notify_mail'];?>" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Change Login URL: </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_sec_options[change_login_url]" id="eblc_change_login_url" class="regular-text ltr" placeholder="https://example.com/test-login" value="<?php echo $eblc_sec_options['change_login_url'];?>"  />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> 404 Page Redirection To: </th>
					        <td>
								<p>
									<label>
										<input type="text" name="eblc_sec_options[404_redirect_url]" id="eblc_404_redirect_url"  class="regular-text ltr" placeholder="https://example.com/redirected-page" value="<?php echo $eblc_sec_options['404_redirect_url'];?>" />
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Google Analytics Code: </th>
					        <td>
								<p>
									<label>
										<textarea rows="5" name="eblc_sec_options[google_analytics_code]" id="eblc_google_analytics_code"  class="regular-text ltr" placeholder="Google analytics code" ><?php echo $eblc_sec_options['google_analytics_code'];?></textarea>
									</label>
								</p>
							</td>
					    </tr>
					    <tr valign="top">
					        <th scope="row"> Any Other Javascript Code: </th>
					        <td>
								<p>
									<label>
										<textarea rows="5" name="eblc_sec_options[custom_javascript_code]" id="eblc_custom_javascript_code"  class="regular-text ltr" placeholder="Javascript code" ><?php echo $eblc_sec_options['custom_javascript_code'];?></textarea>
									</label>
								</p>
							</td>
					    </tr>
					</table>
					<?php  submit_button(); ?>
				</form>
		 	</div>

		</div>  			
		
	</div>

	<div class="eblc_ads col-md-2" style="margin-top: 30px;">
 		<a href="https://jannatqualitybacklinks.com/backlink-service/" target="_blank"><img style="width: 90%;" src="<?php echo plugins_url('img/ad.png', EBLC_PLUGIN_FILE) ?>"/></a>
 	</div>

</div>

<div class="eblc_general_result">
	<?php
		eblc_broken_links(); 
	?>
</div>

<div class="eblc_autolinking_result">
	<?php
		eblc_auto_links(); 
	?>
</div>

<div class="eblc_shortener_result">
	<?php
		eblc_shorten_links(); 
	?>
</div>

<div class="eblc_redirection_result">
	<?php
		eblc_redirection_links(); 
	?>
</div>


<script type="text/javascript">
	jQuery(document).ready( function($) {
		$(document).on( 'change', '.eblc-options-form input', function() {
			if( $(this).attr( 'type' ) == 'checkbox' ) {
				if( $(this).val() == '1'){
					$(this).parent().children().eq(0).val('0');
					$(this).val('0');
				} else {
					$(this).parent().children().eq(0).val('1');
					$(this).val('1');
				}
			}
		});

		// $('.eblc_general_result').show();
		// $('.eblc_shortener_result').hide();
		// $('.eblc_redirection_result').hide();

		$(document).on( 'click', '.eblc_check_options .nav-item', function() {
			// $('.eblc_general_result').hide();
			// $('.eblc_shortener_result').hide();
			// $('.eblc_redirection_result').hide();

			// $('.eblc_' + $(this).attr('tab_name') + '_result').show();
			location.href = $('#eblc_global_url').val() + '&eblc_tab=' + $(this).attr('tab_name');	
		});

		switch ( $('#eblc_global_url_tab').val() ) {
			case '':
				$('.eblc_general_result').show();
				$('.eblc_autolinking_result').hide();
				$('.eblc_shortener_result').hide();
				$('.eblc_redirection_result').hide();
				break;
			case 'eblc_general':
				$('.eblc_general_result').show();
				$('.eblc_autolinking_result').hide();
				$('.eblc_shortener_result').hide();
				$('.eblc_redirection_result').hide();
				break;
			case 'eblc_auto_linking':
				$('.eblc_general_result').hide();
				$('.eblc_autolinking_result').show();
				$('.eblc_shortener_result').hide();
				$('.eblc_redirection_result').hide();
				break;
			case 'eblc_url_shortener':
				$('.eblc_general_result').hide();
				$('.eblc_autolinking_result').hide();
				$('.eblc_shortener_result').show();
				$('.eblc_redirection_result').hide();
				break;
			case 'eblc_redirection':
				$('.eblc_general_result').hide();
				$('.eblc_autolinking_result').hide();
				$('.eblc_shortener_result').hide();
				$('.eblc_redirection_result').show();
				break;
			case 'eblc_security_others':
				$('.eblc_general_result').hide();
				$('.eblc_autolinking_result').hide();
				$('.eblc_shortener_result').hide();
				$('.eblc_redirection_result').hide();
				break;
		}

	});  			
</script>