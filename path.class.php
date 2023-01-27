<?php


class Path {

	private $token = "";
	private $api_endpoint = "https://api.path.net/";

	function __construct($token = '') {
		$this->token = $token;
	}


	//Request token with credentials
	public function token($username, $password) {
		if(empty($username)) {
			throw new Exception("Error: `username` not provided", 1);
		}
		if(empty($password)) {
			throw new Exception("Error: `password` not provided", 1);
		}
		$data = $this->callAPI("POST", "token", ["grant_type" => "password", "username"  => $username, "password" => $password]);
	
		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);
		if(empty($data["access_token"])) return false;

		$this->token = $data["access_token"];

		return true;
	}
	public function token_verify() {
		$data = $this->callAPI("GET", "token/verify");
	
		if(isset($data["detail"])) return false;
		if(empty($data["access_token"])) return false;

		$this->token = $data["access_token"];

		return true;
	}

	public function get_token() {
		return $this->token;
	}

	//Rules

	public function rules($ip = NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "rules");

		if(!isset($data["rules"])) return [];

		if(is_null($ip) == false) {
			return  array_values(array_filter($data["rules"], function($v, $k) {
				global $ip;
			    return $this->ip_in_range($ip, $v["destination"]);
			}, ARRAY_FILTER_USE_BOTH));
		}

		return $data["rules"];
	}

	//Rules
	public function rule($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "rules/".$rule_id);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return  $data;
	}	

	public function addrule($rule = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("POST", "rules", $rule, true);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}	

	public function deleterule($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("DELETE", "rules/".$rule_id);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}



	//Rate Limiters
	public function ratelimiters($ip = NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "rate_limiters");

		if(!isset($data["rate_limiters"])) return [];

		if(is_null($ip) == false) {
			return array_values(array_values(array_filter($data["rate_limiters"], function($v, $k) {
				global $ip;
			    return preg_match("/\[$ip\]/", $v["comment"]);
			}, ARRAY_FILTER_USE_BOTH)));
		}

		return $data["rate_limiters"];
	}

	public function ratelimiter($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "rate_limiters/".$rule_id);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return  $data;
	}	

	public function addratelimiter($info = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("POST", "rate_limiters", $info, true);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}	

	public function updateratelimiter($ratelimiter_id, $info = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("POST", "rate_limiters/".$ratelimiter_id, $info);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}	

	public function deleteratelimiter($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("DELETE", "rate_limiters/".$rule_id);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}	


	//Filters
	public function availablefilters() {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "filters/available");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return  $data["filters"];
	}		

	public function filters($ip = NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("GET", "filters");

		if(!isset($data["filters"])) return [];

		if(is_null($ip) == false) {
			return array_values(array_values(array_filter($data["filters"], function($v, $k) {
				global $ip;
			    return str_replace("/32", "", $v["settings"]["addr"]) == $ip;
			}, ARRAY_FILTER_USE_BOTH)));
		}

		return $data["filters"];
	}	

	public function addfilter($filter_type, $filter = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("POST", "filters/".$filter_type, $filter, true);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}		

	public function removefilter($filter_type, $filter_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("DELETE", "filters/".$filter_type."/".$filter_id);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}	

	public function attack_history($prefix = NULL, $start = NULL, $end = NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);

		$query = "";
		if($prefix != NULL) {
			$query .= "?prefix=".$prefix;
		}

		$data = $this->callAPI("GET", "attack_history".$query);

		if(!isset($data["attack_history"])) return [];

		return $data["attack_history"];
	}

	public function is_ip_blocked($ip=NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		if($ip == NULL) throw new Exception("Error: Invalid IP.");

		$data = $this->callAPI("GET", "abuse_filtered/" . $ip);

		if($data['detail'] && $data['detail'] == "Source is not filtered for abuse.") return false;

		return true;
	}

	public function abuse_filtered() {
		if($this->token == "") throw new Exception("Error: No token found", 1);

		$data = $this->callAPI("GET", "abuse_filtered");
		return $data;
	}

	public function dns_zones() {
		if($this->token == "") throw new Exception("Error: No token found");

		$data = $this->callAPI("GET", "zones");
		return $data;
	}

	public function dns_zone($id) {
		if($this->token == "") throw new Exception("Error: No token found");

		$data = $this->callAPI("GET", "zones/" . $id . '/records');
		return $data;
	}

	public function add_dns_zone_record($zone, $record) {
		if($this->token == "") throw new Exception("Error: No token found");

		$data = $this->callAPI("POST", "zones/" . $zone . "/records", $record, true);
		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}

	public function update_dns_zone_record($zone, $record, $updated_record) {
		if($this->token == "") throw new Exception("Error: No token found");

		$data = $this->callAPI("PATCH", "zones/" . $zone . "/records/" . $record, $updated_record, true);
		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}

	public function delete_dns_zone_record($zone, $record) {
		if($this->token == "") throw new Exception("Error: No token found");

		$data = $this->callAPI("DELETE", "zones/" . $zone . "/records/" . $record);
		if(isset($data['detail'])) throw new Exception("Error: " . json_encode($data['detail']), 1);

		return $data;
	}

	//Helper functions

	//Function to check if IP is inside range
	private function ip_in_range( $ip, $range ) {
		return true;
		if ( strpos( $range, '/' ) == false ) {
			$range .= '/32';
		}
		// $range is in IP/CIDR format eg 127.0.0.1/24
		list( $range, $netmask ) = explode( '/', $range, 2 );
		$range_decimal = ip2long( $range );
		$ip_decimal = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal = ~ $wildcard_decimal;
		return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
	}

	//Curl wrapper for requests
	private function callAPI($method, $url, $data = false, $sendAsJson = false){
		$curl = curl_init();
		$headers = array(
			'User-Agent: Path Network api wrapper for PHP (github.com/pathnetwork/php-api-class)'
		);


		switch ($method){
			case "POST":
			if($sendAsJson) $headers[] = 'Content-Type: application/json';
			curl_setopt($curl, CURLOPT_POST, 1);
			if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, ($sendAsJson) ? json_encode($data) : $data);
			break;
			case "PUT":
			if($sendAsJson) $headers[] = 'Content-Type: application/json';
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");	
			if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, ($sendAsJson) ? json_encode($data) : $data);				
			break;
			case "DELETE":
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			break;
			default:
			if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
		}

		
		if($this->token != "" && $url != "token") {
			$headers[] = 'Authorization: Bearer '.$this->token;
		}

		curl_setopt($curl, CURLOPT_URL, $this->api_endpoint . $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);

		if (curl_errno($curl)) { 
			throw new Exception("Error: ".curl_error($curl), 1);
		}

		curl_close($curl);

		$response = json_decode($result, true);



		return $response;
	}

}

