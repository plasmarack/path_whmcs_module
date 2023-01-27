<?php

namespace WHMCS\Module\Addon\PathFirewall;

use WHMCS\Database\Capsule;
use \Exception;

class PathClass {
    private $token = "";
    private $url = "https://api.path.net/";

    function __construct($username, $password, $url) {
        $this->username = $username;
        $this->password = $password;
        $this->url = $url;
        
        $token = Capsule::table('tbladdonmodules')->where('module', 'pathfirewall')->where('setting', 'API_TOKEN')->first();
        $this->token = $token->value;

        if(!str_ends_with($this->url, '/')) $this->url .= '/';
    }

    public function token() {
        $response = $this->callAPI('token', 'POST', http_build_query([ 'username' => $this->username, 'password' => $this->password ]));
        if(isset($response['detail']) || !isset($response['access_token'])) return false;

        $this->token = $response['access_token'];
        Capsule::table('tbladdonmodules')->where('module', 'pathfirewall')->where('setting', 'API_TOKEN')->update([ 'value' => $this->token ]);

        return true;
    }

    public function attack_history() {
        $response = $this->callAPI('attack_history');
        if(isset($response['detail'])) return false;

        return $response['attack_history'];
    }

    public function rules($ip = NULL) {
        $response = $this->callAPI('rules' . ($ip !== NULL ? '?destination=' . $ip : ''));
        if(isset($response['detail'])) return false;

        return $response['rules'];
    }

	public function filters($ip = NULL) {
		
		$data = $this->callAPI("filters", "GET");

		if(!isset($data["filters"])) return [];

		if(is_null($ip) == false) {
			return array_values(array_values(array_filter($data["filters"], function($v, $k) {
				global $ip;
			    return str_replace("/32", "", $v["settings"]["addr"]) == $ip;
			}, ARRAY_FILTER_USE_BOTH)));
		}

		return $data["filters"];
	}

    private function callAPI($endpoint, $method = "GET", $data = [], $headers = []) {
        $curl = curl_init();

        $headers[] = 'User-Agent: Path Network, Inc. PHP API Wrapper';
        
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if(is_array($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if($this->token !== "") {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($curl, CURLOPT_URL, $this->url . $endpoint);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($curl);
        if(curl_errno($curl)) throw new Exception('An error occurred whilst processing an API request.', 1);
        curl_close($curl);
        
        $response = json_decode($result, true);
        if(isset($response['detail']) && $response['detail'] === "Not authenticated") {
            $this->token();
            return $this->callAPI($endpoint, $method, $data, $headers);
        }

        return $response;
    }

    public function rule($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rules/".$rule_idm, "GET");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}

    public function addrule($rule = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rules", "POST", $rule);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}

    public function deleterule($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rules/".$rule_id, "DELETE");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}

    //Filters
	public function availablefilters() {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("filters/available", "GET");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return  $data["filters"];
	}	

	public function addfilter($filter_type, $filter = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("filters/".$filter_type, "POST", $filter);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}		

	public function removefilter($filter_type, $filter_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("filters/".$filter_type."/".$filter_id, "DELETE");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}

    //Rate Limiters
	public function ratelimiters($ip = NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rate_limiters", "GET");

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
		
		$data = $this->callAPI("rate_limiters/".$rule_id, "GET");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return  $data;
	}

    public function addratelimiter($info = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rate_limiters", "POST", $info);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}	

	public function updateratelimiter($ratelimiter_id, $info = []) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rate_limiters/".$ratelimiter_id, "POST", $info);

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return $data;
	}	

	public function deleteratelimiter($rule_id) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		
		$data = $this->callAPI("rate_limiters/".$rule_id, "DELETE");

		if(isset($data["detail"])) throw new Exception("Error: ".json_encode($data["detail"]), 1);

		return (isset($data['acknowledged'])) ? $data['acknowledged'] : false;
	}

	public function abuse_filtered() {
		if($this->token == "") throw new Exception("Error: No token found", 1);

		$data = $this->callAPI("abuse_filtered", "GET");
		return $data;
	}

	public function is_ip_blocked($ip=NULL) {
		if($this->token == "") throw new Exception("Error: No token found", 1);
		if($ip == NULL) throw new Exception("Error: Invalid IP.");

		$data = $this->callAPI("abuse_filtered/" . $ip, "GET");

		if($data['detail'] && $data['detail'] == "Source is not filtered for abuse.") return false;

		return true;
	}
}

?>