<?php
// ini_set("display_errors", 1);
// error_reporting(E_ALL);

/*
Plugin Name: My Verified ID Connect
Plugin URI: http://myverifiedid.com
Description: This plugins helps you create My Verified ID login and register buttons. The login and register process only takes one click.
Version: Beta 1.0
Author: MyVerifiedID
License: GPL
*/
require_once "includes/config.php";

//Get options from wordpress settings
  $myverifiedid_connect = maybe_unserialize(get_option('myverifiedid_connect'));
if(!empty($myverifiedid_connect)){
  $mviConfiguration = array_merge($mviConfiguration,$myverifiedid_connect);
}

//Add action to load function when the login page loads 
add_action('login_form', 'MyVerifiedID_SignInButton');

/*
* function : MyVerifiedID_SignInButton
* Created Date :31/10/2013
* Modified Date :31/10/2013
* @params : Null
* Use : loads MyVerifiedID login button when login form exists
*/
add_shortcode('mvi-signin-button',"MyVerifiedID_signin_shortcode");

function MyVerifiedID_signin_shortcode(){
  extract( shortcode_atts( array(), $atts ));

   return MyVerifiedID_connect_button(true);
}


add_shortcode('mvi-signup-button',"MyVerifiedID_signup_shortcode");

function MyVerifiedID_signup_shortcode(){

   
     extract( shortcode_atts( array(), $atts ));

     return MyVerifiedID_register_button(true);
}



function MyVerifiedID_register_button(){

  global $mviConfiguration,$myverifiedid_connect;

  if(!empty($mviConfiguration['mvi_signin_url'])){

      if($mviConfiguration['mvi_signup_button']=="")
          $mviConfiguration['mvi_signup_button'] = "a";

      return  "<div class='mvi-login' style='padding:5px 0;'><a class='login' href='".$mviConfiguration['mvi_signin_url']."'><img src='".WP_PLUGIN_URL."/wp-myverifiedid-connect/images/signup-style-".$mviConfiguration['mvi_signup_button'].".jpg' style='max-width:100%'></a></div>";
  }



}


function MyVerifiedID_SignInButton(){
    global $mviConfiguration,$myverifiedid_connect;

    if($mviConfiguration["mvi_display_in_login"] == "on"){

      echo MyVerifiedID_connect_button(true);
    }
}

/*Add action to load MyVerifiedID_connect_button function when the application loads */ 
add_action('init', 'MyVerifiedID_connect_button');

/*
* function : MyVerifiedID_connect_button
* Created Date :31/10/2013
* Modified Date :31/10/2013
* @params : booleen $loggedIn, default true
* Use : loads connect button functionality
*/

function MyVerifiedID_connect_button($loggedIn=false){


  global $mviConfiguration,$myverifiedid_connect;


  //Doesn`t support in ajax call
  if(!preg_match("/admin-ajax.php|wp-load.php/", basename($_SERVER['REQUEST_URI']))){
    global $UID;

    if(isset($_REQUEST['mvi_connect_login'])){
        $authUrl=$_REQUEST['mvi_connect_login'];
        $authUrl=str_replace("*","&",$authUrl);
        wp_redirect( $authUrl );
        exit();
    }


    //Redirect home if logout
    //Reset token session
    if (isset($_REQUEST['logout'])) {
      unset($_SESSION['access_token']);
      header('Location: '.site_url());
    }

    if (isset($_SESSION['access_token']) && $UID!=0) {
        update_user_meta($UID, 'mvi_connect_token', $_SESSION['access_token']);
    }
    if (!$loggedIn) {
      if (!class_exists('MVIClient')) {
        require_once 'includes/mviClientApi.php';
        require_once 'includes/mviAuthClass.php';
      }
      if(session_id() == '') {
        session_start();
      } 
    }

    if (isset($_REQUEST['code']) || $loggedIn==true) {
      $MVIClient = new MVIClient();
      $MVIClient->setClientId($mviConfiguration['mvi_client_id']);
      $MVIClient->setClientSecret($mviConfiguration['mvi_client_secret']);
      $MVIClient->setRedirectUri($mviConfiguration['mvi_redirect_uri']);
      $MVIAuthClass = new MVIAuthClass($MVIClient);

      //kill session if new login
      if (isset($_REQUEST['code'])) {
            if (strval($_SESSION['state']) !== strval($_REQUEST['state'])) {
              die("The session state ({$_SESSION['state']}) didn't match the state parameter ({$_REQUEST['state']})");
              exit();
            }


            try {
                $MVIClient->AuthenticateClient();
                $_SESSION['access_token'] = $MVIClient->getAccessToken();
            } catch (Exception $e) {
                  unset($_SESSION['access_token']);
                  print "<p style=\"color:red\">Please enter valid API Credentials<p>\n";
                  echo '<meta http-equiv="refresh" content="3; url='.site_url().'">';
                  exit();
            }
      }
      //new token
      if (isset($_SESSION['access_token'])) {
        $MVIClient->setAccessToken($_SESSION['access_token']);
      }
      //if token then grab data
      if ($MVIClient->getAccessToken()) {
        $token_obj = json_decode($MVIClient->getAccessToken());
        $token_obj->access_token;
           
       
        //Check if user exists and/or create
        if (isset($_REQUEST['code'])) {
     $MVIUser = $MVIAuthClass->User->user($token_obj->access_token);


          try {
            $MVIUser = $MVIAuthClass->User->user($token_obj->access_token);



          } catch (Exception $e) {
              unset($_SESSION['access_token']);
              print "<p style=\"color:red\">Please login with MyVerifiedID<p>\n";
              echo '<meta http-equiv="refresh" content="3; url='.site_url().'">';
              exit();
          }
          
          $_mvi_id=$MVIUser['uid'];
          $_mvi_email=$MVIUser['email'];
          

          $_mvi_displayName=$MVIUser['display_name'];
          $_mvi_photo=$MVIUser['profile_picture'];
          $_mvi_seal=$MVIUser['profile_seal_code'];

      


          if($_mvi_id){
            if($UID!=0){
                $UID=$UID;
                if(!get_user_meta($UID, 'mvi_connect_user_id')){
                   
                }
            }else{//not logged into wp


              $users=get_users(array('meta_key' => 'mvi_connect_user_id', 'meta_value' => $_mvi_id));

              $UID=(int)$users[0]->ID;
              if(!$UID){
                $new_user=true;
                $user_name=$_mvi_id;
                $arr_user_name=explode(" ",$user_name);
                $user_name=$arr_user_name[0];//user first name
                $user_name=sanitize_user( $user_name );
                $user_name=str_replace(array(" ","."),"",$user_name);
                $user = username_exists( $user_name );

     
                if ( $user ) { //try last name
                    $user_name=$arr_user_name[1];
                    $user_name=sanitize_user( $user_name );
                    $user_name=str_replace(array(" ","."),"",$user_name);
                    $user = username_exists( $user_name ); 
                   
                }
                if ( $user ) { //try first & last name
                    $user_name=$_mvi_id;
                    $user_name=sanitize_user( $user_name );
                    $user_name=str_replace(array(" ","."),"",$user_name);
                    $user = username_exists( $user_name );
                }
                if ( $user ) { //if username happens to exsit tie a random 3 digit number to the end 
                    $user_name=$arr_user_name[0];
                    $user_name=sanitize_user( $user_name );
                    $user_name=str_replace(array(" ","."),"",$user_name);
                    $user_name=$user_name.rand(100, 999);
                    $user = username_exists( $user_name );
                }
                if ( !$user ) { //if no user create them
                      $random_password = wp_generate_password( 12, false );
                      $UID = wp_create_user( $user_name, $random_password, $_mvi_email );
                      wp_update_user( array ( 'ID' => $UID, 'user_nicename' =>  $MVIUser['first_name']." ".$MVIUser['last_name'] ) ) ;

                      if(!is_int($UID)){
                          $user=get_user_by_email($_mvi_email);
                          $UID=$user->ID;
                          //echo "mvi_email: ".$_mvi_email."<br>";
                      }else{
                          update_user_meta($UID, 'nickname', $_mvi_displayName);
                          update_user_meta($UID, 'display_name', $MVIUser['first_name']." ".$MVIUser['last_name']);
                         
                          //budypress functions
                          if(function_exists('wds_bp_check')){
                              mvi_connect_bp_user($UID,$_mvi_displayName,$_mvi_photo);//buddypress.php
                          }
                      }
                      update_user_meta($UID, 'mvi_connect_user_id', $_mvi_id);
                }else{//if username already exists even after random (maybe put in loop to check name so will always make a new one if random lands twicw)
                      echo "<p style=\"color:red\">Please try to connect again!<p>\n";
                      echo '<meta http-equiv="refresh" content="3; url='.site_url().'">';
                      exit();
                }
              }
              //login user and redirect
              wp_set_auth_cookie( $UID, false, is_ssl() );
            }
           
            update_user_meta($UID, 'mvi_connect_token', $_SESSION['access_token']);
            update_user_meta($UID, 'mvi_profile_picture', $_mvi_photo);
            update_user_meta($UID, 'mvi_seal', $_mvi_seal);
            update_user_meta($UID, 'nickname',  $MVIUser['first_name']." ".$MVIUser['last_name']);
            update_user_meta($UID, 'display_name',  $MVIUser['first_name']." ".$MVIUser['last_name']);
            update_user_meta($UID, 'profile_url',  $MVIUser['profile_url']);

          
           
            wp_update_user( array ( 'ID' => $UID, 'user_nicename' => $MVIUser['first_name']." ".$MVIUser['last_name'],'display_name'=> $MVIUser['first_name']." ".$MVIUser['last_name']) ) ;
            wp_redirect( site_url() );
            exit();
          }
        }
        if (isset($_REQUEST['code'])) {
          $_SESSION['access_token'] = $MVIClient->getAccessToken();
        }
      }
      //display button
      if ($loggedIn==true) {
        if (!$MVIClient->getAccessToken()) {
            if($_SESSION['state']){
              $state=$_SESSION['state'];
            }else{
              $state = mt_rand();
            }
            $MVIClient->setState($state);
            $_SESSION['state'] = $state;
            $authUrl = $MVIClient->getClientUrl();
            if($mviConfiguration['mvi_client_id'] && $mviConfiguration['mvi_client_secret'] && $mviConfiguration['mvi_redirect_uri']){
                add_query_arg( 'mvi_connect_login', $authUrl );
                if ($mviConfiguration['mvi_load_style'] == "")
                  $mviConfiguration['mvi_load_style'] = "a";

                
                return "<div class='mvi-login' style='padding:5px 0;'><a class='login' href='".$authUrl."'><img src='".WP_PLUGIN_URL."/wp-myverifiedid-connect/images/signIn-style-".$mviConfiguration['mvi_load_style'].".jpg' style='max-width:100%'></a></div>";
            }
        }
      }
    }
  }
}


  function cp_login_head() {
    
    
    

    if (is_user_logged_in()) :
      global $current_user;
      $current_user = wp_get_current_user();
       $mvi_connect_token = get_user_meta($current_user->id, "mvi_connect_token", true ); 
      if(!empty($mvi_connect_token)){
        $access_token = json_decode($mvi_connect_token);
        if($access_token->access_token){
          
          $_mvi_profile_picture = file_get_contents('http://api.myverifiedid.com/api/users/profile_picture?access_token='.$access_token->access_token);   
        
          update_user_meta($current_user->id, 'mvi_profile_picture', $_mvi_profile_picture);
        
        }
      }
      
      
      $display_user_name = $current_user->display_name;
      $logout_url = cp_logout_url();
      ?>
      <?php _e( 'Welcome,', APP_TD ); ?> <strong><?php echo $display_user_name; ?></strong> [ <a href="<?php echo CP_DASHBOARD_URL; ?>"><?php _e( 'My Dashboard', APP_TD ); ?></a> | <a href="<?php echo $logout_url; ?>"><?php _e( 'Log out', APP_TD ); ?></a> ]&nbsp;
    <?php else : ?>
      <?php _e( 'Welcome,', APP_TD ); ?> <strong><?php _e( 'visitor!', APP_TD ); ?></strong> [ <a href="<?php echo appthemes_get_registration_url(); ?>"><?php _e( 'Register', APP_TD ); ?></a> | <a href="<?php echo wp_login_url(); ?>"><?php _e( 'Login', APP_TD ); ?></a> ]&nbsp;
    <?php endif;

  }

add_action('wp_logout','MyVerifiedID_logout');
function MyVerifiedID_logout(){
  unset($_SESSION['access_token']);
}  

/*
* Function : 
* Modified Date :
* @params : None
* Use : loads when login form exists
*/

//add button to login form
add_action('bp_after_sidebar_login_form', 'MyVerifiedID_login_form'); 
function MyVerifiedID_login_form(){

  echo '<link rel="stylesheet" href="'.WP_PLUGIN_URL . '/wp-myverifiedid-connect/css/style.css" type="text/css" media="all" />';    
    MyVerifiedID_connect_button();

}


add_filter('get_avatar', 'MyVerifiedID_insert_avatar', 5, 5);

function MyVerifiedID_insert_avatar($avatar = '', $id_or_email, $size = 96, $default = '', $alt = false) {

  $id = 0;
  if (is_numeric($id_or_email)) {
    $id = $id_or_email;
  } else if (is_string($id_or_email)) {
    $u = get_user_by('email', $id_or_email);
    $id = $u->id;
  } else if (is_object($id_or_email)) {
    $id = $id_or_email->user_id;
  }
  if ($id == 0) return $avatar;

   $mvi_connect_token = get_user_meta($id, "mvi_connect_token", true ); 

    if(!empty($mvi_connect_token)){
      $access_token = json_decode($mvi_connect_token);
    if($access_token->access_token){
      
      $_mvi_profile_picture = @file_get_contents('http://api.myverifiedid.com/api/users/profile_picture?access_token='.$access_token->access_token);   
    
    }  
  }

  $pic = $_mvi_profile_picture;
  if (!$pic || $pic == '') return $avatar;
  $avatar = preg_replace('/src=("|\').*?("|\')/i', 'src=\'' . $pic . '\'', $avatar);
  return $avatar;
}

add_filter('bp_core_fetch_avatar', 'MyVerifiedID_bp_insert_avatar', 3, 5);

function MyVerifiedID_bp_insert_avatar($avatar = '', $params, $id) {
    if(!is_numeric($id) || strpos($avatar, 'gravatar') === false) return $avatar;
    $pic = get_user_meta($id, 'mvi_profile_picture', true);
    if (!$pic || $pic == '') return $avatar;
    $avatar = preg_replace('/src=("|\').*?("|\')/i', 'src=\'' . $pic . '\'', $avatar);
    return $avatar;
}

if (!defined('MVI_PLUGIN_BASENAME')) define('MVI_PLUGIN_BASENAME', plugin_basename(__FILE__));
add_filter('plugin_action_links', 'MyVerifiedID_plugin_action_links', 10, 2);

function MyVerifiedID_plugin_action_links($links, $file) {

  if ($file != MVI_PLUGIN_BASENAME) return $links;
  $settings_link = '<a href="' . menu_page_url('myverifiedid-connect', false) . '">' . esc_html(__('Settings', 'myverifiedid-connect')) . '</a>';
  array_unshift($links, $settings_link);
  return $links;
}



function my_plugin_load_first()
{
  $path = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
  if ( $plugins = get_option( 'active_plugins' ) ) {
    if ( $key = array_search( $path, $plugins ) ) {
      array_splice( $plugins, $key, 1 );
      array_unshift( $plugins, $path );
      update_option( 'active_plugins', $plugins );
    }
  }
}

add_action("activated_plugin", "my_plugin_load_first");



//add button to registration form
add_action('register_form','MyVerifiedID_register_form'); 
function MyVerifiedID_register_form(){
  echo "<link rel='stylesheet' href='".plugins_url('css/style.css',__FILE__)."'  type='text/css' media='all' />";    

    MyVerifiedID_connect_button(true);

}
/*
Options Page
*/
require_once (trailingslashit(dirname(__FILE__)) . "myverifiedid-settings.php");
if (class_exists('MyverifiedIDFBSettings')) {
  $MyverifiedIDFBSettings = new MyverifiedIDFBSettings();
  if (isset($MyverifiedIDFBSettings)) {
    add_action('admin_menu', array(&$MyverifiedIDFBSettings,
      'MyVerifiedID_Menu'
    ) , 1);
  }
}




?>