<?php
class EventImporter {
    public $token;
    public $api_key;
    public $host = 'http://api.mixpanel.com/';
    public function __construct($token_string,$api_key) {
        $this->token = $token_string;
		$this->api_key = $api_key;
    }
    function identify($distinct_id, $ip, array $properties=array()) {
        $params = array(
            '$distinct_id' => $distinct_id,
			'$ip' => $ip,
        );
		if(count($properties) > 0) {
			$params['$set'] = $properties;
		}
        if (!isset($params['$token'])){
            $params['$token'] = $this->token;
        }
        $url = $this->host . 'engage/?data=' . base64_encode(json_encode($params)) . "&api_key=$this->api_key";
        //you still need to run as a background process
		echo "$url\n";
        echo $this->createRequest($url);
    }
    function track($event, $properties=array()) {
        $params = array(
            'event' => $event,
            'properties' => $properties
            );

        if (!isset($params['properties']['token'])){
            $params['properties']['token'] = $this->token;
        }
        $url = $this->host . 'import/?data=' . base64_encode(json_encode($params)) . "&api_key=$this->api_key";
        //you still need to run as a background process
		echo "$url\n";
        echo $this->createRequest($url);
    }
	private function createRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
        $result = curl_exec($ch);
        curl_close($ch);
		return $result;
	}
}