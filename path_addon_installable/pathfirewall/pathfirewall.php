<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\PathFirewall\Admin\AdminDispatcher;
use WHMCS\Module\Addon\PathFirewall\Client\ClientDispatcher;
use WHMCS\Module\Addon\PathFirewall\PathClass;

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

function pathfirewall_config()
{
    return [
        'name' => "Path Firewall",
        'description' => "Client Firewall Management Module for Path Network, Inc. Customers.",
        'author' => "Path Network, Inc.",
        'language' => "English",
        'version' => "1.0",
        'fields' => [
            'API_BASE' => [
                'FriendlyName' => "API Base URL",
                'Type' => "text",
                'Size' => "25",
                'Default' => "https://api.path.net/"
            ],
            'API_USERNAME' => [
                'FriendlyName' => "API Username",
                'Type' => "text",
                'Size' => "25"
            ],
            'API_PASSWORD' => [
                'FriendlyName' => "API Password",
                'Type' => "password",
                'Size' => "25"
            ]
        ]
    ];
}

function pathfirewall_activate()
{
    Capsule::table('tbladdonmodules')->insert([
        [
            'module' => 'pathfirewall',
            'setting' => 'API_TOKEN',
            'value' => ''
        ]
    ]);

    return [
        'status' => 'success',
        'description' => 'Enabled Firewall Management Module'
    ];
}

function pathfirewall_deactivate()
{
    Capsule::table('tbladdonmodules')->where('module', 'pathfirewall')->where('setting', 'API_TOKEN')->delete();

    return [
        'status' => 'success',
        'description' => 'Disabled Firewall Management Module'
    ];
}

function pathfirewall_clientarea($vars)
{
    $dispatcher = new ClientDispatcher();

    $vars['path'] = new PathClass($vars['API_USERNAME'], $vars['API_PASSWORD'], $vars['API_BASE']);

    $vars['servers'] = [];
    $GLOBALS['authorized_ips'] = [];

    $current_user = new \WHMCS\Authentication\CurrentUser;
    $client = $current_user->client();
    
    foreach($client->services as $service) {
        if($service->domainstatus === 'Active') {
            $vars['servers'][] = [ 'ip' => $service->dedicatedip, 'service' => $service->product->name, 'id' => $service->id ];

            $GLOBALS['authorized_ips'][] = $service->dedicatedip . '/32';

            foreach(preg_split('/[\n\r]+/', $service->assignedips) as $inetnum) {
                if(empty($inetnum)) continue;

                if(preg_match('~^(?:[0-9]{1,3}\.){3}[0-9]{1,3}/[0-9][0-9]~',$inetnum)) {
                    $GLOBALS['authorized_ips'][] = $inetnum;
                    
                    $range = [];
                    $cidr = explode('/', $inetnum);
                    $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
                    $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);

                    for($i = ip2long($range[0]); $i < ip2long($range[1]); $i++) {
                        $vars['servers'][] = [ 'ip' => long2ip($i), 'service' => $service->product->name, 'id' => $service->id ];
                    }

                    continue;
                }

                if(!filter_var($inetnum, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;

                $GLOBALS['authorized_ips'][] = $inetnum . '/32';
                $vars['servers'][] = [ 'ip' => $inetnum, 'service' => $service->product->name ];
            }
        }
    }

    function ip_in_range( $ip, $range ) {
        return true;
		if(!explode('/', $range)[1]) $range .= '/32';

		list($range, $netmask) = explode('/', $range, 2);
		$range_decimal = ip2long($range);
		$ip_decimal = ip2long($ip);
		$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
		$netmask_decimal = ~ $wildcard_decimal;
		return (($ip_decimal & $netmask_decimal) === ($range_decimal & $netmask_decimal));
	}

    function authorized_ip($inetnum) {
        if(!explode('/', $inetnum)[1]) $inetnum .= '/32';

        if(in_array($inetnum, $GLOBALS['authorized_ips'])) return true;

        foreach($GLOBALS['authorized_ips'] as $prefix) {
            if(explode("/", $prefix)[1] < 32 && ip_in_range(explode("/", $inetnum)[0], $prefix)) return true;
        }
    
        return false;
    }

    $vars['authorized_ip'] = 'authorized_ip';

    if(isset($_GET['action'])) {
        header('Content-Type: application/json');
        return $dispatcher->dispatch($_GET['action'], $vars);
    }

    if(isset($_GET['server'])) {
        $vars['server'] = $_GET['server'];
        return $dispatcher->dispatch('server', $vars);
    }

    return $dispatcher->dispatch('servers', $vars);
}

?>