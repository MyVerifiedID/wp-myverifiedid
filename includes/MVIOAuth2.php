<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
class MVIOAuth2{
  public $clientId;
  public $clientSecret;
  public $accessToken;
  public $redirectUri;
  public $state;
  protected $respHttpCode;
  protected $respHeaders;
  protected $respBody;

  public $token_url;
  public $authorize_url;

  
  public function __construct() {
    global $mviConfiguration;


    if (! empty($mviConfiguration['mvi_token_url'])) {
      $this->token_url = $mviConfiguration['mvi_token_url'];
    }

    if (! empty($mviConfiguration['mvi_authorize_url'])) {
      $this->authorize_url = $mviConfiguration['mvi_authorize_url'];
    }

    if (! empty($mviConfiguration['mvi_client_id'])) {
      $this->clientId = $mviConfiguration['mvi_client_id'];
    }

  }



  protected function setrespHttpCode($respHttpCode){

    $this->respHttpCode = $respHttpCode;

  }


  protected function getrespHttpCode(){

    return $this->respHttpCode;

  }

  protected function setrespHeaders($respHeaders){

    $this->respHeaders = $respHeaders;

  }


  protected function getrespHeaders(){

    return $this->respHeaders;

  }

  protected function setrespBody($respBody){

    $this->respBody = $respBody;

  }


  protected function getrespBody(){

    return $this->respBody;

  }



  public function AuthenticateClient() {
    global $mviConfiguration;  
    if (isset($_GET['code_lg'])) {


      // We got here from the redirect from a successful authorization grant, fetch the access token
      
      $postParams = array(
          'code' => $_GET['code_lg'],
          'grant_type' => 'authorization_code',
          'redirect_uri' => $this->redirectUri,
          'client_id' => $this->clientId,
          'client_secret' => $this->clientSecret
      );


      $requestParams = new stdClass();
      $requestParams->URL = $mviConfiguration['token_url'];
      $requestParams->Headers = array();
      $requestParams->Method = 'POST';
      $requestParams->UserAgent = 'mvi-1.0';
      $requestParams->Body = $postParams;


      $request = $this->makeRequest($requestParams);




      if ($this->getrespHttpCode() == 200) {

        $this->setAccessToken($this->getrespBody());
        $this->accessToken['created'] = time();
        return $this->getAccessToken();
      } else {
        $response = $this->getrespBody();

        $decodedResponse = json_decode($response, true);
        if ($decodedResponse != $response && $decodedResponse != null && $decodedResponse['error']) {
          $response = $decodedResponse['error'];
        }
        throw new ExceptionAuthhandler("Enable to get access token, message: '$response'", $this->getrespHttpCode());
      }
    }

    $authUrl = $this->createAuthUrl($service);
    header('Location: ' . $authUrl);
  } 


  public function authenticatedRequest($request) {

    return $this->makeRequest($request);
  }

    public function makeRequest($request) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request->URL);
    if ($request->Body) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $request->Body);
    }
    if ($request->Headers && is_array($request->Headers)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, array_unique($request->Headers));
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->Method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'mvi-1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
   


      curl_setopt($ch, CURLOPT_VERBOSE, 1);



    // $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL, $request->URL);
    // if ($request->Body) {
    //   curl_setopt($ch, CURLOPT_POSTFIELDS, $request->Body);
    // }
    // if ($request->Headers && is_array($request->Headers)) {
    //   curl_setopt($ch, CURLOPT_HTTPHEADER, array_unique($request->Headers));
    // }
    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->Method);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    // curl_setopt($ch, CURLOPT_FAILONERROR, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    // curl_setopt($ch, CURLOPT_HEADER, true);
    $data = @curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);




    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = @curl_errno($ch);
    $error = @curl_error($ch);
    @curl_close($ch);
    if ($errno != CURLE_OK) {
      throw new ExceptionAuthhandler('HTTP Error: (' . $errno . ') ' . $error);
    }

    // Parse out the raw response into usable bits

    $rawResponseHeaders = substr($data, 0, $header_size);
    $responseBody = substr($data, $header_size);

    
    
    $rawResponseHeaders = str_replace("\r\n\r\n","",$rawResponseHeaders);
    $responseHeaderLines = explode("\r\n", $rawResponseHeaders);
    array_shift($responseHeaderLines);
    $responseHeaders = array();
    foreach ($responseHeaderLines as $headerLine) {
      list($header, $value) = explode(': ', $headerLine, 2);
      if (isset($responseHeaders[$header])) {
        $responseHeaders[$header] .= "\n" . $value;
      } else {
        $responseHeaders[$header] = $value;
      }
    }

   
    $this->setrespHttpCode((int)$httpCode);
    $this->setrespHeaders($responseHeaders);
    $this->setrespBody($responseBody);

  
    return $request;
  }

  public function getClientUrl() {
    global $mviConfiguration;
    $params = array(
        'response_type=code',
        'redirect_uri=' . urlencode($this->redirectUri),
        'client_id=' . urlencode($this->clientId),
        
    );

    if (isset($this->state)) {
      $params[] = 'state=' . urlencode($this->state);
    }
    $params = implode('&', $params);

    return $mviConfiguration['authorize_url'] . "?$params";
  }

  public function setAccessToken($accessToken) {
    $accessToken = json_decode($accessToken, true);

    if ($accessToken == null) {
      throw new ExceptionAuthhandler("Could not json decode the access token");
    }
    //if (! isset($accessToken['access_token']) || ! isset($accessToken['expires_in']) || ! isset($accessToken['refresh_token'])) {
    if (! isset($accessToken['access_token']) || ! isset($accessToken['expires_in']) ) {
      throw new ExceptionAuthhandler("Invalid token format");
    }
    $this->accessToken = $accessToken;
  }

  public function getAccessToken() {
    return json_encode($this->accessToken);
  }


  public function setState($state) {
    $this->state = $state;
  }

 

}
