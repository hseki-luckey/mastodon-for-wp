<?php
/**
 * Intellectual Property of #Mastodon
 * 
 * @copyright (c) 2017, #Mastodon
 * @author V.A. (Victor) Angelier <victor@thecodingcompany.se>
 * @version 1.0
 * @license http://www.apache.org/licenses/GPL-compatibility.html GPL
 * 
 */
namespace theCodingCompany;

use theCodingCompany\HttpRequest;

/**
 * oAuth class for use at Mastodon
 */
trait oAuth
{
    /**
     * Our API to use
     * @var type 
     */
    private function mastodon_api_url(){
        return get_option('mastodon_instance', '');
    }
    
    /**
     * Default headers for each request
     * @var type 
     */
    private $headers = array(
        "Content-Type" => "application/json; charset=utf-8", 
        "Accept" => "*/*"
    );
        
    /**
     * Holds our client_id and secret
     * @var array 
     */
    private function credentials(){
        $credentials = array(
            "client_id"     =>  get_option('mastodon_client_id', ''),
            "client_secret" =>  get_option('mastodon_client_secret', ''),
        );

        return $credentials;
    }

    /**
     * App config
     * @var type 
     */
    private function app_config(){
        $app_config = array(
            "client_name"   => get_option('mastodon_client_name', ''),
            "redirect_uris" => "urn:ietf:wg:oauth:2.0:oob",
            "scopes"        => "read write",
            "website"       => home_url('/')
        );

        return $app_config;
    }
    
    /**
     * Get the API endpoint
     * @return type
     */
    public function getApiURL(){
        return "https://{$this->mastodon_api_url()}";
    }
    
    /**
     * Get Request headers
     * @return type
     */
    public function getHeaders(){
        $bearer = get_option('mastodon_bearer', '');

        if(!empty($bearer)){
            $auth = 'Bearer '.$bearer;
            $this->headers["Authorization"] = $auth;
        }
        return $this->headers;
    }
    
    /**
     * Start at getting or creating app
     */
    public function getAppConfig(){
        //Get singleton instance
        $http = HttpRequest::Instance("https://{$this->mastodon_api_url()}");
        $config = $http::post(
            "api/v1/apps", //Endpoint
            $this->app_config(),
            $this->headers
        );
        //Check and set our credentials
        if(!empty($config) && isset($config["client_id"]) && isset($config["client_secret"])){
            $credentials = $this->credentials();
            $credentials['client_id'] = $config['client_id'];
            $credentials['client_secret'] = $config['client_secret'];
            return $credentials;
        }else{
            return false;
        }
    }
    
    /**
     * Set the correct domain name
     * @param type $domainname
     */
    public function setMastodonDomain($domainname = ""){
        if(!empty($domainname)) return $domainname;
    }
    
    /**
     * Create authorization url
     */
    public function getAuthUrl(){
        $credentials = $this->credentials();
        if(is_array($credentials) && isset($credentials["client_id"])){           
            //Return the Authorization URL
            return "https://{$this->mastodon_api_url()}/oauth/authorize/?".http_build_query(array(
                    "response_type"    => "code",
                    "redirect_uri"     => "urn:ietf:wg:oauth:2.0:oob",
                    "scope"            => "read write",
                    "client_id"        => $credentials["client_id"]
                ));
        }        
        return false;        
    }
    
    /**
     * Handle our bearer token info
     * @param type $token_info
     * @return boolean
     */
    private function _handle_bearer($token_info = null){
        if(!empty($token_info) && isset($token_info["access_token"])){
            return $token_info["access_token"];
        }
        return false;
    }
    
    /**
     * Get access token
     * @param type $auth_code
     */
    public function getAccessToken($auth_code = ""){
        $credentials = $this->credentials();
        if(is_array($credentials) && isset($credentials["client_id"])){
            //Request access token in exchange for our Authorization token
            $http = HttpRequest::Instance("https://{$this->mastodon_api_url()}");
            $token_info = $http::Post(
                "oauth/token",
                array(
                    "grant_type"    => "authorization_code",
                    "redirect_uri"  => "urn:ietf:wg:oauth:2.0:oob",
                    "client_id"     => $credentials["client_id"],
                    "client_secret" => $credentials["client_secret"],
                    "code"          => $auth_code
                ),
                $this->headers
            );
            
            //Save our token info
            return $this->_handle_bearer($token_info);
        }
        return false;
    }
    
    /**
     * Authenticate a user by username and password
     * @param type $username usernam@domainname.com
     * @param type $password The password
     */
    private function authUser($username = null, $password = null){
        if(!empty($username) && stristr($username, "@") !== FALSE && !empty($password)){
            $credentials = $this->credentials();
            if(is_array($credentials) && isset($credentials["client_id"])){
                //Request access token in exchange for our Authorization token
                $http = HttpRequest::Instance("https://{$this->mastodon_api_url()}");
                $token_info = $http::Post(
                    "oauth/token",
                    array(
                        "grant_type"    => "password",
                        "client_id"     => $credentials["client_id"],
                        "client_secret" => $credentials["client_secret"],
                        "username"      => $username,
                        "password"      => $password,
                    ),
                    $this->headers
                );
                
                //Save our token info
                return $this->_handle_bearer($token_info);
            }
        }        
        return false;
    }
}
