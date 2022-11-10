<?php
/*********************************************************************
    Run osTicket installer based on environment variables for
    osticket-dockerized

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.
**********************************************************************/


require('setup.inc.php');
require_once INC_DIR.'class.installer.php';

require_once('../include/ost-config.php');

// Create the installer based on the dummy config file
define('OSTICKET_CONFIGFILE','../include/ost-config.php');
$installer = new Installer(OSTICKET_CONFIGFILE);

$status = $installer->install(
    array(
        "name" => "my osticket-dockerized install",
        "email" => "osticket@docker.local",
        "fname" => "Admin",
        "lname" => "Admin",
        "admin_email" => ADMIN_EMAIL,
        "username" => md5(ADMIN_EMAIL),
        "passwd" => getenv("OST_ADMIN_PASSWD") ?: die("Missing required environment variable for install: OST_ADMIN_PASSWD"),
        "passwd2" => getenv("OST_ADMIN_PASSWD") ?: die("Missing required environment variable for install: OST_ADMIN_PASSWD"),
        "prefix" => TABLE_PREFIX,
        "dbhost" => DBHOST,
        "dbname" => DBNAME,
        "dbuser" => DBUSER,
        "dbpass" => DBPASS,
        "lang_id" => "en",
    ),
);

if ($status) {
    $staff = Staff::lookup(1);
    $staff->forcePasswdRest();
    $staff->save();
    echo "Succesfully run installation. You can now sign in with username " . ADMIN_EMAIL . " and password " . getenv("OST_ADMIN_PASSWD");
} elseif (!empty($status->prefix)) { // If we previously ran the installation, we can silently exit
    var_dump($installer->errors);
    exit(1);
}


?>