<?php
include_once(dirname(__FILE__).'/geoip2.php');

class geoIpAddressInfo
{
    public $info;
	
	public function __construct($ip)
    {
        $this->run($ip);
    }
	
    public function run($ip)
    {
		set_error_handler('geoIpAddressInfo::error', E_USER_NOTICE);

		try {
			foreach(['GeoLite2-City', 'GeoLite2-Country', 'GeoLite2-ASN'] as $f) {
				if(!file_exists(dirname(__FILE__).'/db/'.$f.'.mmdb')) { trigger_error("The database files does not exist or is not readable."); return; }
			}
			
			$reader = new Reader(dirname(__FILE__).'/db/GeoLite2-City.mmdb');
			$record = $reader->city($ip);
			
			if(gettype($record) === 'object')
			{
				$this->info['city']     = ['name' => $record->city->name, 'geonameid' => $record->city->geonameId, 'postalcode' => $record->postal->code];
				$this->info['location'] = ['latitude' => $record->location->latitude, 'longitude' => $record->location->longitude, 'timezone' => $record->location->timeZone];
				
				$reader = new Reader(dirname(__FILE__).'/db/GeoLite2-Country.mmdb');
				$record = $reader->country($ip);
				$this->info['country']   = ['name' => $record->country->name,   'geonameid' => $record->country->geonameId,   'code' => $record->country->isoCode];
				$this->info['continent'] = ['name' => $record->continent->name, 'geonameid' => $record->continent->geonameId, 'code' => $record->continent->code];
				
				$reader = new Reader(dirname(__FILE__).'/db/GeoLite2-ASN.mmdb');
				$record = $reader->asn($ip);
				$this->info['asn'] = ['autonomousSystemNumber' => $record->autonomousSystemNumber, 'autonomousSystemOrganization' => $record->autonomousSystemOrganization, 'ipaddress' => $record->ipAddress];
				
				$this->info['provider'] = 'GeoLite2';
			}
			
			if(empty($this->info) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && file_exists(dirname(__FILE__).'/db/GeoLiteCity.dat'))
			{
				include_once(dirname(__FILE__).'/geoip.php');
				$geoip = ($geoip = geoip_info($ip, dirname(__FILE__).'/db/GeoLiteCity.dat')) ? get_object_vars($geoip) : null;
				if(gettype($record) === 'array')
				{
					$this->info['city']      = ['name' => $geoip['city']];
					$this->info['location']  = ['latitude' => $geoip['latitude'], 'longitude' => $geoip['longitude'], 'timezone' => $geoip['timezone']];
					$this->info['country']   = ['name' => $geoip['country_name'],  'code' => $geoip['country_code']];
					$this->info['continent'] = ['code' => $geoip['continent_code']];
					$this->info['provider'] = 'GeoLite';
				}
			}
			
			if(gettype($this->info) === 'array') {
				$this->info['version'] = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'IPV4' : (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPV6' : null);
			}
		}
		catch(Exception $e) {
			$this->info['error'] = $e->getMessage();
		}
		
		restore_error_handler();
    }
	
	public function error($errNo, $errStr, $errFile, $errLine, $errContext)
    {
		throw new Exception($errStr);
	}
}
