<?php

//Check Curl is present or not

if (!function_exists('curl_init')) {
  throw new Exception('The API needs the CURL PHP extension.');
}else{
  $version = curl_version();
  $ssl_supported= ($version['features'] & CURL_VERSION_SSL);
  if(!$ssl_supported) 
    throw new Exception('Protocol https not supported or disabled in libcurl');
}

if (!function_exists('json_decode')) {
  throw new Exception('The API needs the JSON PHP extension.');
}


// hack around with the include paths a bit so the library 'just works'
$cwd = dirname(__FILE__);
set_include_path("$cwd" . PATH_SEPARATOR . ":" . get_include_path());


require_once "MVIOAuth2.php";

class Exceptionbasehandler extends Exception {}
class ExceptionAuthhandler extends Exceptionbasehandler {}

class MVIClient {
  
  protected $auth;


  // Used to track authenticated state, can't discover services after doing authenticate()
  private $is_authenticate = false;

  public function __construct() {
    global $mviConfiguration;
    // Create our worker classes
    $this->auth = new $mviConfiguration['authClass'];

  }

  public function setAuthClass($authClassName) {
    $this->auth = null;
    $this->auth = new $authClassName();
  }

  public function AuthenticateClient() {
    $this->is_authenticate = true;
    return $this->auth->AuthenticateClient();
  }

  
  public function getClientUrl() {
    return $this->auth->getClientUrl();
  }

  public function setAccessToken($accessToken) {
    if ($accessToken == null || 'null' == $accessToken) {
      $accessToken = null;
    }
    $this->auth->setAccessToken($accessToken);
  }

  public function getAccessToken() {
    $token = $this->auth->getAccessToken();
    return (null == $token || 'null' == $token) ? null : $token;
  }

  public function setState($state) {
    $this->auth->setState($state);
  }


  public function setClientId($clientId) {
    global $mviConfiguration;
    $mviConfiguration['oauth2_client_id'] = $clientId;
    $this->auth->clientId = $clientId;
  }


  public function setClientSecret($clientSecret) {
    global $mviConfiguration;
    $mviConfiguration['oauth2_client_secret'] = $clientSecret;
    $this->auth->clientSecret = $clientSecret;
  }

  public function setRedirectUri($redirectUri) {
    global $mviConfiguration;
    $mviConfiguration['oauth2_redirect_uri'] = $redirectUri;
    $this->auth->redirectUri = $redirectUri;
  }


  public function setUseObjects($useObjects) {
    global $mviConfiguration;
    $mviConfiguration['use_objects'] = $useObjects;
  }

}
