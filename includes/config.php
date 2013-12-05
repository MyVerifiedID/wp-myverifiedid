<?php

global $mviConfiguration;

 $mvi_api_root_url = "http://api.myverifiedid.com";  
 $mviConfiguration = array(

    'mvi_client_id' => "MVI-CLIENT-ID",
    'mvi_client_secret' => "MVI-CLIENT-SECRET",
    'mvi_redirect_uri' => "MVI-REDIRECT-URI",
    'site_name' => "YOUR-SITE-ROOT-URL",
    'authClass'    => 'MVIOAuth2',
    'basePath' => $mvi_api_root_url,
    'token_url' => $mvi_api_root_url.'/oauth/token',
    'authorize_url' => $mvi_api_root_url.'/oauth/authorize',
    'utilities'=>array('user'=>
                   array("url"=>$mvi_api_root_url."/api/users/","method" => "me")
                 )
  );

