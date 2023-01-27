<?php

namespace WHMCS\Module\Addon\PathFirewall\Client;

class Controller {
    
    /**
     * View all related servers / services
     */

    public function servers($vars) {
        if(isset($_GET['action'])) return [];

        return [
            'pagetitle' => 'Firewall Manager',
            'breadcrumb' => [
                'index.php?m=pathfirewall' => 'Firewall Manager',
            ],
            'templatefile' => 'client/servers',
            'requirelogin' => true,
            'vars' => [
                'servers' => $vars['servers']
            ]
        ];
    }

    /**
     * View individual server / service
     */

    public function server($vars) {
        if(isset($_GET['action'])) return [];
        if(!$vars['authorized_ip']($vars['server'])) return header('Location: /index.php?m=pathfirewall');

        $page = isset($_GET['page']) ? $_GET['page'] : 'rules';
        
        $variables = [
            'server' => $vars['server'],
            'page' => $page,
            'ip' => $_GET['server']
        ];

        return [
            'pagetitle' => 'Firewall Manager - ' . $vars['server'],
            'breadcrumb' => [
                'index.php?m=pathfirewall' => 'Firewall Manager',
                'index.php?m=pathfirewall&server=' . $vars['server'] => 'Managing ' . $vars['server']
            ],
            'templatefile' => 'client/server',
            'requirelogin' => true,
            'vars' => $variables
        ];
    }
}

?>