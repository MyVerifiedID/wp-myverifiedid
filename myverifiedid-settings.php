<?php
/*
Settings Page
*/

$mvi_status = "normal";
echo '<link rel="stylesheet" href="'.WP_PLUGIN_URL . '/wp-myverifiedid-connect/css/style.css" type="text/css" media="all" />';    
if(isset($_POST['myverifiedid_update_options'])) {
	if($_POST['myverifiedid_update_options'] == 'Y') {
    foreach($_POST AS $k => $v){
      $_POST[$k] = stripslashes($v);
    }
		update_option("myverifiedid_connect", maybe_serialize($_POST));
		$newmvi_status = 'update_success';
	}
}

if(!class_exists('MyverifiedIDFBSettings')) {
class MyverifiedIDFBSettings {
function MyVerifiedID_Options_Page() {
    global $mviConfiguration;


  $domain = get_option('siteurl');
  $domain = str_replace(array('http://', 'https://'), array('',''), $domain);
  $domain = str_replace('www.', '', $domain);
  $a = explode("/",$domain);
  $domain = $a[0]; 
	?>

	<div class="wrap">
	<div id="newfb-options">
	<div id="newfb-title"><h2>My Verified ID Connect Settings</h2></div>
	<?php
	global $newmvi_status;
	if($newmvi_status == 'update_success')
		$message =__('Configuration updated', 'myverifiedid-connect') . "<br />";
	else if($newmvi_status == 'update_failed')
		$message =__('Error while saving options', 'myverifiedid-connect') . "<br />";
	else
		$message = '';

	if($message != "") {
	?>
		<div class="updated"><strong><p><?php
		echo $message;
		?></p></strong></div><?php
	} ?>
  
  <?php
  if (!function_exists('curl_init')) {
  ?>
    <div class="error"><strong><p>MVI CURL PHP extension. Contact your server adminsitrator!</p></strong></div>
  <?php
  }else{
    $version = curl_version();
    $ssl_supported= ($version['features'] & CURL_VERSION_SSL);
    if(!$ssl_supported){
    ?>
      <div class="error"><strong><p>Protocol https not supported or disabled in libcurl. Contact your server adminsitrator!</p></strong></div>
    <?php
    }
  }
  if (!function_exists('json_decode')) {
    ?>
      <div class="error"><strong><p>MVI needs the JSON PHP extension. Contact your server adminsitrator!</p></strong></div>
    <?php
  }
  ?>
  
	<div id="newfb-desc">
	<p><?php _e('This plugins helps you create My Verified ID login and register buttons. The login and register process only takes one click and you can fully customize the buttons with images and other assets.', 'myverifiedid-connect'); ?></p>
	<h3><?php _e('Setup', 'myverifiedid-connect'); ?></h3>
  <p>
  <?php _e('<ol><li><a href="'.$mviConfiguration['basePath'].'/oauth/applications/new" target="_blank">Create a My Verified ID app!</a></li>', 'myverifiedid-connect'); ?>
  <?php _e('<li>Fill out <b>Redirect uri</b> field with:<b>'.get_option('siteurl').'/wp-login.php</b></li>', 'myverifiedid-connect'); ?>
  <?php _e('<li>Click on <b>Save changes</b> and you will get <b>Redirect url</b>, <b>Client Id</b> and <b>Client secret</b> which you have to copy and past below.</li>', 'myverifiedid-connect'); ?>
  <?php _e('<li><b>Save changes!</b></li></ol>', 'myverifiedid-connect'); ?>
  
  
  </p>
  
  </div>

	<!--right-->
	<div class="postbox-container" style="float:right;width:30%;">
	<div class="metabox-holder">
	<div class="meta-box-sortables">

	<!--about-->
	<div id="newfb-about" class="postbox">
	<h3 class="hndle"><?php _e('About this plugin', 'myverifiedid-connect'); ?></h3>
	<div class="inside"><ul>
  
  <li><a href="http://support.myverifiedid.com" target="_blank"><?php _e('<b>MyVerifiedID Support</b>!', 'myverifiedid-connect'); ?></a></li>
  <li><br></li>
	<li><a href="https://myverifiedid.com" target="_blank"><?php _e('<b>Click here</b> to visit our website', 'myverifiedid-connect'); ?></a></li>
	<li><br></li>
	</ul></div>
	</div>
	<!--about end-->

	<!--others-->
	<!--others end-->

	</div></div></div>
	<!--right end-->

	<!--left-->
	<div class="postbox-container" style="float:left;width: 69%;">
	<div class="metabox-holder">
	<div class="meta-box-sortabless">

	<!--setting-->
	<div id="myverifiedid-setting" class="postbox">
	<h3 class="hndle"><?php _e('Settings', 'myverifiedid-connect'); ?></h3>
	<?php $myverifiedid_connect = maybe_unserialize(get_option('myverifiedid_connect')); ?>


	<form method="post" action="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/options-general.php?page=myverifiedid-connect">
	<input type="hidden" name="myverifiedid_update_options" value="Y">

	<table class="form-table">
		<tr>
		<th scope="row"><?php _e('MyVerifiedID Client ID:', 'myverifiedid-connect'); ?></th>
		<td>
		<input type="text" name="mvi_client_id" value="<?php echo @$myverifiedid_connect['mvi_client_id']; ?>" />
		</td>
		</tr>  
      
		<tr>
		<th scope="row"><?php _e('MyVerifiedID Client Secret:', 'myverifiedid-connect'); ?></th>
		<td>
		<input type="text" name="mvi_client_secret" value="<?php echo @$myverifiedid_connect['mvi_client_secret']; ?>" />
		</td>
		</tr>



		<tr>
		<th scope="row"><?php _e('Redirect url:', 'myverifiedid-connect'); ?></th>
		<td>
		<input type="text" name="mvi_redirect_uri" value="<?php echo @$myverifiedid_connect['mvi_redirect_uri']; ?>" />
		</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('SignUp Url:', 'myverifiedid-connect'); ?></th>
			<td>

				<input type="text" size="50" name="mvi_signin_url" value="<?php echo @$myverifiedid_connect['mvi_signin_url']; ?>" />
	     
			</td>
		</tr>	
		
		<tr>
		<th scope="row"><?php _e('SignIn button in login page:', 'myverifiedid-connect'); ?></th>
		<td>

			
			<input type="checkbox" name="mvi_display_in_login" id="mvi_display_in_login" <?php if(isset($myverifiedid_connect['mvi_display_in_login']) && $myverifiedid_connect['mvi_display_in_login'] == 'on'){?> checked <?php } ?> />
		</td>
		</tr>


		
		<tr>
			<th scope="row"><?php _e('Choose SignIn Button:', 'myverifiedid-connect'); ?></th>
			<td>
	      <?php if(!isset($myverifiedid_connect['mvi_load_style'])) $myverifiedid_connect['mvi_load_style'] = 'a'; ?>
			<input name="mvi_load_style" id="mvi_load_style_yes" value="a" type="radio" <?php if(isset($myverifiedid_connect['mvi_load_style']) && $myverifiedid_connect['mvi_load_style']){?> checked <?php } ?>> <img src="<?php echo WP_PLUGIN_URL?>/wp-myverifiedid-connect/images/signIn-style-a.jpg"/>  &nbsp;&nbsp;&nbsp;&nbsp;

	    	<input name="mvi_load_style" id="mvi_load_style_no" value="b" type="radio" <?php if(isset($myverifiedid_connect['mvi_load_style']) && $myverifiedid_connect['mvi_load_style'] == 'b'){?> checked <?php } ?>> <img src="<?php echo WP_PLUGIN_URL?>/wp-myverifiedid-connect/images/signIn-style-b.jpg"/>		
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Choose SignUp Button:', 'myverifiedid-connect'); ?></th>
			<td>
	      <?php if(!isset($myverifiedid_connect['mvi_signup_button'])) $myverifiedid_connect['mvi_signup_button'] = 'a'; ?>
			<input name="mvi_signup_button" id="mvi_signup_button_a" value="a" type="radio" <?php if(!isset($myverifiedid_connect['mvi_signup_button']) || $myverifiedid_connect['mvi_signup_button'] == "a"){?> checked <?php } ?>> <img src="<?php echo WP_PLUGIN_URL?>/wp-myverifiedid-connect/images/signup-style-a.jpg"/>  &nbsp;&nbsp;&nbsp;&nbsp;

			</td>
		</tr>	

		

	</table>

	<p class="submit">
	<input style="margin-left: 10%;" type="submit" name="Submit" value="<?php _e('Save Changes', 'myverifiedid-connect'); ?>" />
	</p>
	</form>
	</div>
	<!--setting end-->

	<!--others-->
	<!--others end-->

	</div></div></div>
	<!--left end-->

	</div>
	</div>
	<?php
}

function MyVerifiedID_Menu() {
	add_options_page(__('My Verified ID Connect'), __('MyVerifiedID Connect'), 'manage_options', 'myverifiedid-connect', array(__CLASS__,'MyVerifiedID_Options_Page'));
}

}
}
?>
