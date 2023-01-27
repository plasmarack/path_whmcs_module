<?php

namespace WHMCS\Module\Addon\PathFirewall\Client;

class ClientDispatcher {
    public function dispatch($action, $parameters)
    {
        if (!$action) {
            $action = 'index';
        }

        $controller = new Controller();

        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }
    }
}

?>