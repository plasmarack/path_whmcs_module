<?php

use WHMCS\ClientArea;
use WHMCS\Database\Capsule;

define('CLIENTAREA', true);

require __DIR__ . '/init.php';

function isValidJSON($str) {
	json_decode($str);
	return json_last_error() == JSON_ERROR_NONE;
}

function ip_in_range( $ip, $range ) {
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
function getIpRange(  $cidr) {

    list($ip, $mask) = explode('/', $cidr);

    $maskBinStr =str_repeat("1", $mask ) . str_repeat("0", 32-$mask );      //net mask binary string
    $inverseMaskBinStr = str_repeat("0", $mask ) . str_repeat("1",  32-$mask ); //inverse mask

    $ipLong = ip2long( $ip );
    $ipMaskLong = bindec( $maskBinStr );
    $inverseIpMaskLong = bindec( $inverseMaskBinStr );
    $netWork = $ipLong & $ipMaskLong; 

    $start = $netWork+1;//ignore network ID(eg: 192.168.1.0)

    $end = ($netWork | $inverseIpMaskLong) -1 ; //ignore brocast IP(eg: 192.168.1.255)
    return array('firstIP' => $start, 'lastIP' => $end );
}

function getEachIpInRange ( $cidr) {
    $ips = array();
    $range = getIpRange($cidr);
    for ($ip = $range['firstIP']; $ip <= $range['lastIP']; $ip++) {
        $ips[] = long2ip($ip);
    }
    return $ips;
}
$ca = new ClientArea();

$ca->setPageTitle('Firewall Manager');

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('firewall.php', 'Firewall Manager');

$ca->initPage();

$ca->requireLogin(); 

$currentUser = new \WHMCS\Authentication\CurrentUser;
$client = $currentUser->client();

$clientName = Capsule::table('tblclients')
->where('id', '=', $client->id)->value('firstname');
$ca->assign('clientname', $clientName);


$email = Capsule::table('tblclients')
->where('id', '=', $client->id)->value('email');
$ca->assign('email', $email);



$command = 'GetClientsProducts';
$postData = array(
	'clientid' => $client->id,
	'stats' => true,
);

$results = localAPI($command, $postData);

// Limits
$maxrules = 100;
$maxfilters = 20;

// Legacy
$max_filters_for_ip = array();
$max_rules_for_ip = array();
$ranges = array();


$undeleteable_rules = [];


$ips = array();

//if(isset($_GET['oops'])) {
//	var_dump($results["products"]["product"]); exit();
//}

foreach ($results["products"]["product"] as $key => $value) {
	if($value["status"] != "Active") continue;

	if(!filter_var($value["dedicatedip"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
	// $inRange = false;
	// foreach ($ranges as $range) {
	// 	if(ip_in_range($value["dedicatedip"], $range)) $inRange = true;
	// }
	// if($inRange == false) continue;

	$t = array();
	$t["ip"] = $value["dedicatedip"];
	$t["service"] = $value;
	$t["last"] = "Unavailable";
	$ips[$value["dedicatedip"]] = $t;
	foreach (preg_split('/[\n\r]+/', $value["assignedips"]) as $key1 => $value1) {

		if(empty($value1)) continue;
		if(preg_match('~^(?:[0-9]{1,3}\.){3}[0-9]{1,3}/[0-9][0-9]~',$value1,$subnet)) {
			foreach(getEachIpInRange ($value1) as $ipr) {
				$t = array();
				$t["ip"] = $ipr;
				$t["service"] = $value;
				$t["last"] = "Unavailable";
				$ips[$ipr] = $t;	
			}
			continue;
		}
		if(!filter_var($value1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
		// $inRange = false;
		// foreach ($ranges as $range) {
		// 	if(ip_in_range($value1, $range)) $inRange = true;
		// }
		// if($inRange == false) continue;

		$t = array();
		$t["ip"] = $value1;
		$t["service"] = $value;
		$t["last"] = "Unavailable";
		$ips[$value1] = $t;
	}
}


if(!isset($_GET["action"])) {
	$ca->assign("ips", $ips);
	if(isset($_GET['ip'])) {

		if(filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) && isset($ips[$_GET["ip"]])) {

			Menu::addContext("firewall", "firewall");

			$ca->addToBreadCrumb('firewall.php?ip='.$_GET['ip'], 'Viewing rules for '.$_GET['ip']);


			$ca->assign("ip", $_GET['ip']);

			# Define the template filename to be used without the .tpl extension

			$ca->setTemplate('firewall_view');

			$ca->output();
			exit;

		} else {
			$ca->assign("error", "Invalid IP address or you do not own this IP address");
		}
	}

    /**
     * Set a context for sidebars
     *
     * @link http://docs.whmcs.com/Editing_Client_Area_Menus#Context
     */
    Menu::addContext("firewall", "firewall");

    # Define the template filename to be used without the .tpl extension

    $ca->setTemplate('firewall');

    $ca->output();

    exit;
}

function errorAndDie($message) {
	echo json_encode(["success" => false, "message" => $message]);
	die();
}

if (!class_exists("Path")) {
	require 'path.class.php';
}
$path = new Path();

if ($token) {
	$path = new Path($token);

	if(!$path->token_verify()) {
		if(!$path->token("change_me", "change_me")) {
			echo json_encode(["success" => false, "message" => "Failed to auth with api"]);
			exit;
		}
		
		$token = $path->get_token();
		$mem_var->set("path_token", $token);
	}
} else {
	$path = new Path();

	if(!$path->token("change_me", "change_me")) {
		echo json_encode(["success" => false, "message" => "Failed to auth with api"]);
		exit;
	}

	$token = $path->get_token();
}

switch ($_GET['action']) {
	case 'getRules':
	if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");

	try {
		$rules = Capsule::table('path_firewall_limits')->where('clientid', $client->id)->value('rulelimit');
		if($rules) $maxrules = $rules;
	} catch(Exception $e) {  }

	$tmp = [];
	$rules = $path->rules($_GET["ip"]);

	foreach($rules as $rule) {
		$rule["nodelete"] = in_array($rule["id"], $undeleteable_rules);
		$tmp[] = $rule;
	}

	echo json_encode(["success" => true, "data" => $tmp, "maxrules" => $maxrules]);
	break;    	

	case 'isThisIPBlocked':
		if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
		if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
		if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
		break;

	case 'getAttackHistory':
	if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");


	echo json_encode(["success" => true, "data" => $path->attack_history($_GET["ip"])]);
	break;  	

	case 'getLastAttacked':
	if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");


	echo json_encode(["success" => true, "data" => $path->attack_history($_GET["ip"])[0]]);
	break;    

	case 'getIPBlocked':
		if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
		if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
		if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");

		echo json_encode([ 'success' => true, 'data' => $path->is_ip_blocked($_GET["ip"]) ]);
		break;

	case 'getBlockedIPs':
		$blocked_ips = array();

		$abuse_filtered_ips = $path->abuse_filtered();

		foreach($abuse_filtered_ips as $filtered_ip) {
			if($ips[$filtered_ip['source']]) array_push($blocked_ips, $filtered_ip['source']);
		}

		echo json_encode([ 'success' => true, 'data' => $blocked_ips ]);
		break;

	case 'addRule':
	if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	try {
		$json_params = file_get_contents("php://input");

		if (strlen($json_params) > 0 && isValidJSON($json_params)) {
			$data = json_decode($json_params, true);		

			if($data["source"] == "0.0.0.0/32") $data["source"] = "0.0.0.0/0";

			try {
				$minimum_cidr = Capsule::table('path_firewall_limits')
->where('clientid', $client->id)->value('cidrlimit');

				if(!$minimum_cidr) $minimum_cidr = 22;
			} catch(Exception $e) {
				$minimum_cidr = 22;
			}

			if(explode("/", $data['source'])[1] < $minimum_cidr && !isset($data['protocol']) && $data['whitelist'] == true) {
				die(json_encode(["success" => false, "data" => "Rules must be more specific."]));
			}

			if(!isset($data["protocol"]) && $data["source"] == "0.0.0.0/0" && $data["whitelist"] == true) {
				echo json_encode(["success" => false, "data" => "Rules must be more specific."]);
				break;
			}

			if(isset($data["protocol"]) && $data["source"] == "0.0.0.0/0" && in_array($data["protocol"], ["udp", "tcp"]) && !isset($data["src_port"]) && !isset($data["dst_port"])) {
				echo json_encode(["success" => false, "data" => "Rules must be more specific."]);
				break;
			}

			if(isset($data["protocol"]) && strtolower($data["protocol"]) == "gre") {
				echo json_encode(["success" => false, "data" => "Protocol GRE is not allowed. Please contact support"]);
				break;
			}


			if($data["destination"] != $_GET['ip']."/32") {
				echo json_encode(["success" => false, "data" => "Invalid destination"]);
				break;
			}

			if(isset($max_rules_for_ip[$_GET['ip']])) {
				$max = $max_rules_for_ip[$_GET['ip']];
		
				if($max["email"] == $email) {
					$maxfilters = $max["max"];
				}
			}

			try {
				$rules = Capsule::table('path_firewall_limits')->where('clientid', $client->id)->value('rulelimit');
				if($rules) $maxrules = $rules;
			} catch(Exception $e) {  }

			if(count($path->rules($_GET["ip"])) >= $maxrules) {
				echo json_encode(["success" => false, "data" => "Max rules reached"]);
				break;
			}

			if(isset($data['priority'])) unset($data['priority']);

			echo json_encode(["success" => true, "data" => $path->addrule($data)]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to add rule"]);
		}
	} catch(Exception $er) {
		if(preg_match("/not a valid IPv4/", $er->getMessage())) {
			echo json_encode(["success" => false, "data" => "Failed to add rule. Source was not a valid IPv4 subnet."]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to add rule"]);
		}
	}
	break;

	case 'removeRule':
	if(!isset($_GET["ip"], $_GET['id'])) errorAndDie("Invalid Request");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");

	if(in_array($_GET['id'], $undeleteable_rules)) {
		echo json_encode(["success" => false, "data" => "This rule cannot be deleted."]);
		return;
	} 

	echo json_encode(["success" => true, "data" => $path->deleterule($_GET['id'])]);
	break;

	case 'getRatelimiters':
	if(!isset($_GET["ip"])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");


	echo json_encode(["success" => true, "data" => $path->ratelimiters($_GET["ip"])]);
	break;  

	case 'addRatelimiter':
	if(!isset($_GET["ip"])) errorAndDie("Invalid Request");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	$json_params = file_get_contents("php://input");

	if (strlen($json_params) > 0 && isValidJSON($json_params)) {
		$data = json_decode($json_params, true);


		$data["comment"] = $data["comment"]. " [".$_GET['ip']."]";

		if(count($path->ratelimiters($_GET["ip"])) >= 20) {
			echo json_encode(["success" => false, "data" => "Max ratelimiters reached"]);
			break;
		}


		echo json_encode(["success" => true, "data" => $path->addratelimiter($data)]);
	} else {
		echo json_encode(["success" => false, "data" => "Failed to add rate limiter"]);
	}
	break;       

	case 'removeRatelimiter':
	if(!isset($_GET["ip"], $_GET['id'])) errorAndDie("Invalid authorization");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	try {
		echo json_encode(["success" => true, "data" => $path->deleteratelimiter($_GET["id"])]);
	} catch(Exception $er) {
		if(preg_match("/referenced/", $er->getMessage())) {
			echo json_encode(["success" => false, "data" => "Ratelimiter already in use."]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to delete rate limiter"]);
		}

	}
	break;    

	case 'getAvailableFilters':
	if(!isset($_GET["ip"])) errorAndDie("Invalid Request");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");



	echo json_encode(["success" => true, "data" => $path->availablefilters()]);

	break;
	case 'getFilters':
	if(!isset($_GET["ip"])) errorAndDie("Invalid Request");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");

	try {
		$filters = Capsule::table('path_firewall_limits')->where('clientid', $client->id)->value('filterlimit');
		if($filters) $maxfilters = $filters;
	} catch(Exception $e) {  }

	if(isset($max_filters_for_ip[$_GET['ip']])) {
		$max = $max_filters_for_ip[$_GET['ip']];

		if($max["email"] == $email) {
			$maxfilters = $max["max"];
		}
	}

	echo json_encode(["success" => true, "data" => $path->filters($_GET['ip']), "maxfilters" => $maxfilters]);

	break;

	case 'addFilter':
	if(!isset($_GET["ip"], $_GET['type'])) errorAndDie("Invalid authorization");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	try {
		$json_params = file_get_contents("php://input");

		if (strlen($json_params) > 0 && isValidJSON($json_params)) {
			$data = json_decode($json_params, true);


			if($data["addr"] != $_GET['ip']) {
				echo json_encode(["success" => false, "data" => "Invalid destination"]);
				break;
			}
			
			try {
				$filters = Capsule::table('path_firewall_limits')->where('clientid', $client->id)->value('filterlimit');
				if($filters) $maxfilters = $filters;
			} catch(Exception $e) {  }

			if(isset($max_filters_for_ip[$_GET['ip']])) {
				$max = $max_filters_for_ip[$_GET['ip']];
		
				if($max["email"] == $email) {
					$maxfilters = $max["max"];
				}
			}

			if(count($path->filters($_GET["ip"])) >= $maxfilters) {
				echo json_encode(["success" => false, "data" => "Max filters reached"]);
				break;
			}

			if(isset($data['max_conn_pps']) && $data['max_conn_pps'] === "") unset($data['max_conn_pps']);


			echo json_encode(["success" => true, "data" => $path->addFilter($_GET['type'], $data)]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to add rule"]);
		}
	} catch(Exception $er) {
		if(preg_match("/filter definition conflicts with an existing/", $er->getMessage())) {
			echo json_encode(["success" => false, "data" => "Filter already exists with these settings."]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to add filter ".$er->getMessage()]);
		}
	}
	break;    



	case 'removeFilter':
	if(!isset($_GET["ip"], $_GET['type'], $_GET['id'])) errorAndDie("Invalid authorization");
	if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )) errorAndDie("Invalid IP address");
	if(!isset($ips[$_GET["ip"]])) errorAndDie("Invalid IP address");
	try {
		echo json_encode(["success" => true, "data" => $path->removefilter($_GET['type'], $_GET["id"])]);
	} catch(Exception $er) {
		if(preg_match("/referenced/", $er->getMessage())) {
			echo json_encode(["success" => false, "data" => "Ratelimiter already in use."]);
		} else {
			echo json_encode(["success" => false, "data" => "Failed to delete rate limiter"]);
		}

	}
	break;    

	default:
	echo json_encode(["success" => false, "message" => "Invalid Action"]);
	break;
}

