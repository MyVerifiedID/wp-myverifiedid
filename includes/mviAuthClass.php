<?php

  class UserResource extends MVIOAuth2{


    
      private $stackParameters = array(
          'trace' => array('type' => 'string', 'location' => 'query'),
          'userIp' => array('type' => 'string', 'location' => 'query'),
          'userip' => array('type' => 'string', 'location' => 'query'),
          'access_token' => array('type' => 'string', 'location' => 'query')
      );
      private $service;
      private $serviceName;
      private $resourceName;
      private $methods;

      public function __construct($service, $serviceName, $resourceName, $resource) {
        $this->service = $service;
        $this->serviceName = $serviceName;
        $this->resourceName = $resourceName;
        $this->methods = $resource['methods'];
      }


    public function user($access_token) {
      $params = array('access_token' => $access_token);
 
      $data = $this->__call('user', array($params));
      return $data;


    }


  public function __call($name, $arguments) {
        global $mviConfiguration;
    if (count($arguments) != 1 && count($arguments) != 2) {
      throw new Exceptionbasehandler("apiClient method calls expect one or two parameter (for example: \$apiClient->buzz->activities->list( array('userId' => '@me')) or when executing a batch request: \$apiClient->buzz->activities->list( array('userId' => '@me'), 'batchKey')");
    }
    if (! is_array($arguments[0])) {
      throw new Exceptionbasehandler("apiClient method parameter should be an array (for example: \$apiClient->buzz->activities->list( array('userId' => '@me'))");
    }
    $batchKey = false;
    if (isset($arguments[1])) {
      if (! is_string($arguments[1])) {
        throw new Exceptionbasehandler("The batch key parameter should be a string, for example: \$apiClient->buzz->activities->list( array('userId' => '@me'), 'batchKey')");
      }
      $batchKey = $arguments[1];
    }



    if (! isset($this->methods[$name])) {
      throw new Exceptionbasehandler("Unknown function: {$this->serviceName}->{$this->resourceName}->{$name}()");
    }
    $method = $this->methods[$name];
    $parameters = $arguments[0];
    // postBody is a special case since it's not defined in the discovery document as parameter, but we abuse the param entry for storing it
    $postBody = null;
    if (isset($parameters['postBody'])) {
      if (is_object($parameters['postBody'])) {
        $this->stripNull($parameters['postBody']);
      }

      // Some APIs require the postBody to be set under the data key.
      if (is_array($parameters['postBody']) && 'buzz' == $this->serviceName) {
        if (!isset($parameters['postBody']['data'])) {
          $rawBody = $parameters['postBody'];
          unset($parameters['postBody']);
          $parameters['postBody']['data'] = $rawBody;
        }
      }

      $postBody = is_array($parameters['postBody']) || is_object($parameters['postBody']) ? json_encode($parameters['postBody']) : $parameters['postBody'];
      // remove from the parameter list so not to trip up the param entry checking & make sure it doesn't end up on the query
      unset($parameters['postBody']);

      if (isset($parameters['optParams'])) {
        $optParams = $parameters['optParams'];
        unset($parameters['optParams']);

        $parameters = array_merge($parameters, $optParams);
      }
    }
  


    if (!isset($method['parameters'])) {
      $method['parameters'] = array();
    }
    
    $method['parameters'] = array_merge($method['parameters'], $this->stackParameters);
    foreach ($parameters as $key => $val) {
      if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
        throw new Exceptionbasehandler("($name) unknown parameter: '$key'");
      }
    }

 
    if (isset($method['parameters'])) {
      foreach ($method['parameters'] as $paramName => $paramSpec) {
        if (isset($paramSpec['required']) && $paramSpec['required'] && ! isset($parameters[$paramName])) {
          throw new Exceptionbasehandler("($name) missing required param: '$paramName'");
        }
        if (isset($parameters[$paramName])) {
          $value = $parameters[$paramName];
          // check to see if the param value matches the required pattern
          if (isset($parameters[$paramName]['pattern']) && ! empty($parameters[$paramName]['pattern'])) {
            if (preg_match('|' . $parameters[$paramName]['pattern'] . '|', $value) == 0) {
              throw new Exceptionbasehandler("($name) invalid parameter format for $paramName: $value doesn't match \"{$parameters[$paramName]['pattern']}\"");
            }
          }
          $parameters[$paramName] = $paramSpec;
          $parameters[$paramName]['value'] = $value;
          // remove all the bits that were already validated in this function & are no longer relevant within the execution chain
          unset($parameters[$paramName]['pattern']);
          unset($parameters[$paramName]['required']);
        } else {
          unset($parameters[$paramName]);
        }
      }
    }

    // Discovery v1.0 puts the canonical method id under the 'id' field.
    if (! isset($method['id'])) {
      $method['id'] = $method['rpcMethod'];
    }

    // Discovery v1.0 puts the canonical path under the 'path' field.
    if (! isset($method['path'])) {
      $method['path'] = $method['restPath'];
    }
      $requestParams = new stdClass();
      $requestParams->Body = $postBody;
      $requestParams->Parameters = $parameters;

      $requestParams->RestBasePath = $mviConfiguration['utilities'][$name]['url'];
      $requestParams->RestPath = $mviConfiguration['utilities'][$name]['method'];
      $requestParams->HttpMethod = $method['httpMethod'];





      // $request = new apiServiceRequest('MVIOAuth2', '', '/rpc/', $method['path'], $method['id'], $method['httpMethod'], $parameters, $postBody);


      return $this->execute($requestParams);
    
  }

  static public function execute($request) {
    global $apiTypeHandlers;

    $result = null;
    $requestUrl = $request->RestBasePath . $request->RestPath;
    
    $queryVars = array();


    foreach ($request->Parameters as $paramName => $paramSpec) {
      // Discovery v1.0 puts the canonical location under the 'location' field.
      if (! isset($paramSpec['location'])) {
        $paramSpec['location'] = $paramSpec['restParameterType'];
      }
      
      if ($paramSpec['location'] == 'path') {
        $uriTemplateVars[$paramName] = $paramSpec['value'];
      } else {
        if ($paramSpec['type'] == 'boolean') {
          $paramSpec['value'] = ($paramSpec['value']) ? 'true' : 'false';
        }
        if (isset($paramSpec['repeated']) && is_array($paramSpec['value'])) {
          foreach ($paramSpec['value'] as $value) {
            $queryVars[] = $paramName . '=' . rawurlencode($value);
          }
        } else {
          $queryVars[] = $paramName . '=' . rawurlencode($paramSpec['value']);
        }
      }
    }
    $queryVars[] = 'alt=json';

    //FIXME work around for the the uri template lib which url encodes the @'s & confuses our servers
    $requestUrl = str_replace('%40', '@', $requestUrl);
    //EOFIX

    //FIXME temp work around to make @groups/{@following,@followers} work (something which we should really be fixing in our API)
    if (strpos($requestUrl, '/@groups') && (strpos($requestUrl, '/@following') || strpos($requestUrl, '/@followers'))) {
      $requestUrl = str_replace('/@self', '', $requestUrl);
    }
    //EOFIX
   
    if (count($queryVars)) {
      $requestUrl .= '?' . implode($queryVars, '&');
    }
 


    // Add a content-type: application/json header so the server knows how to interpret the post body
    
      $requestParams = new stdClass();
      $requestParams->URL = $requestUrl;
      $requestParams->Headers = array();
      $requestParams->Method = 'GET';
      $requestParams->UserAgent = 'mvi-1.0';
      $requestParams->Body = $request->Body;



    $auths = new MVIOAuth2();  
    $httpRequest = $auths->authenticatedRequest($requestParams);

  
    if ($auths->getrespHttpCode() != '200' && $auths->getrespHttpCode() != '201' && $auths->getrespHttpCode() != '204') {
      $responseBody = $auths->getrespBody();
      if (($responseBody = json_decode($responseBody, true)) != null && isset($responseBody['error']['message']) && isset($responseBody['error']['code'])) {
        // if we're getting a json encoded error definition, use that instead of the raw response body for improved readability
        $errorMessage = "Error calling " . $auths->getUrl() . ": ({$responseBody['error']['code']}) {$responseBody['error']['message']}";
      } else {
        $errorMessage = "Error calling " . $auths->getMethod() . " " . $auths->getUrl() . ": (" . $auths->getrespHttpCode() . ") " . $auths->getrespBody();
      }
      throw new apiServiceException($errorMessage);
    }
    $decodedResponse = null;
    if ($auths->getrespHttpCode() != '204') {
      // Only attempt to decode the response, if the response code wasn't (204) 'no content'
      if (($decodedResponse = json_decode($auths->getrespBody(), true)) == null) {
        throw new apiServiceException("Invalid json in service response: " . $httpRequest->getrespBody());
      }
    }
    //FIXME currently everything is wrapped in a data envelope, but hopefully this might change some day
    $ret = isset($decodedResponse['data']) ? $decodedResponse['data'] : $decodedResponse;


    return $ret;
  }



  }


class MVIAuthClass{
  public $User;

  public function __construct(MVIClient $MVIClient) {

    $this->rpcPath = '/rpc';
    $this->restBasePath = '/api/users/';
    $this->version = 'v1';
    $this->serviceName = 'plus';
    $this->methods = 'me';

    $this->User = new UserResource($this, $this->serviceName, 'User', json_decode('{"methods": {"user": {"parameters": {"access_token": {"pattern": "me|[0-9]+", "required": true, "type": "string", "location": "path"}}, "httpMethod": "GET", "path": "me", "response": {"$ref": "User"}}}}', true));    
  }





}

