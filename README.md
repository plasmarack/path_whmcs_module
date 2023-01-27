# path_whmcs_module


#### **Installation Steps:**
___

*Transfer core modules:*

**1.)** `/modules/addons/pathaddons/` --> `/whmcs/modules/addons/pathaddons/`

**2.)** `/path_addon_installable/pathfirewall/` --> `/whmcs/modules/addons/pathfirewall/`
___

*Transfer template & front-end files:*

**3.)** `/templates/*.tpl` --> `/whmcs/templates/${themeName}/*.tpl`

**4.)** `/path_addon_installable/path_firewall/templates/client/js/*.js` --> `/whmcs/templates/${themeName}/js/*.js`
___

*Transfer root pages:*

**5.)** `/firewall.php` --> `/whmcs/firewall.php`

**6.)** `/path.class.php` --> `/whmcs/path.class.php`
___

*Configuration:*

**7.)** Add your username and password to the path.classs.php for the Path Portal. 
___