<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/wp-includes/geoip/index.php');

class maosfood_accessLogger
{
  private $remote_ip   = null;
  private $provider_ip = null;
  private $user_agent  = null;
  private $request_uri = null;
  private $description = null;
  
  public function __construct($status=200)
  {
	global $config;
	
	$this->remote_ip   = $this->get_ip_address();
	$this->provider_ip = gethostbyaddr($this->remote_ip);
	
	$this->accept      = (isset($_SERVER['HTTP_ACCEPT'])     ? $_SERVER['HTTP_ACCEPT']     : null);
	$this->user_agent  = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
	$this->request_uri = (isset($_SERVER['REQUEST_URI'])     ? $_SERVER['REQUEST_URI']     : null);
	$this->referer     = (isset($_SERVER['HTTP_REFERER'])    ? $_SERVER['HTTP_REFERER']    : null);
	$this->session     = (isset($_SESSION['SID'])            ? $_SESSION['SID']            : null);
	
	$this->description = Array('Time: '.date('H:i:s'), 'IP address: '.$this->remote_ip, 'Provider: '.$this->provider_ip, 'Request: '.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].($_SERVER['REQUEST_URI'] == '/' ? null : urldecode($_SERVER['REQUEST_URI'])), 'User-Agent: '.$this->user_agent);
	if(isset($_SERVER['HTTP_REFERER']))         $this->description[] = 'Referer: '.urldecode($_SERVER['HTTP_REFERER']);
	if(isset($_SERVER['HTTP_ACCEPT']))          $this->description[] = 'Accept: '.$_SERVER['HTTP_ACCEPT'];
	if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) $this->description[] = 'Accept-Encoding: '.$_SERVER['HTTP_ACCEPT_ENCODING'];
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $this->description[] = 'Accept-Language: '.$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	
	$this->create_folder($_SERVER['DOCUMENT_ROOT'].'/wp-content/logs');
	if(is_dir($_SERVER['DOCUMENT_ROOT'].'/wp-content/logs') && !file_exists($_SERVER['DOCUMENT_ROOT'].'/wp-content/logs/.htaccess')) file_put_contents($_SERVER['DOCUMENT_ROOT'].'/wp-content/logs/.htaccess', "<RequireAny>\r\n<RequireAll>\r\nRequire all denied\r\n</RequireAll>\r\n</RequireAny>");
	
	$infoIpAddress = new geoIpAddressInfo($this->remote_ip);

    if(gettype($infoIpAddress->info) === 'array') $this->description[] = json_encode($infoIpAddress->info);
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/wp-content/logs/access-'.$status.'-'.date('Y-m-d').'.log', implode("\r\n", $this->description)."\r\n\r\n", FILE_APPEND);
  }
  
  private function get_ip_address()
  {
	$ip_address = '127.0.0.1';
	
	foreach(array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
	{
      if(array_key_exists($key, $_SERVER) === true)
	  {
        foreach(explode(',', $_SERVER[$key]) as $ip)
		{
          $ip = trim($ip);
          if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
		  {
			$ip_address = $ip;
			break 2;
	      }
        }
      }
    }
	
	return $ip_address;
  }
  
  public function create_folder($path)
  {
    if(!is_dir($path) && mkdir($path, 0755)) file_put_contents($path.'/index.html', '<html><body bgcolor="#FFFFFF"></body></html>');
  }
  
}
