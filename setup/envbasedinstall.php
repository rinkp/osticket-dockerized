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
    echo "Succesfully run installation. You can now sign in with username " . ADMIN_EMAIL . " and password " . getenv("OST_ADMIN_PASSWD") . PHP_EOL;
} elseif (!empty($status->prefix)) { // If we previously ran the installation, we can silently exit
    var_dump($installer->errors);
    exit(1);
}

/**
 * Enable changing osticket configuration items
 */
$config = new OsticketConfig();

if (strlen(getenv("OST_HELPDESK_URL") ?: '') > 0) {
    echo '"OST_HELPDESK_URL=' . getenv("OST_HELPDESK_URL") . '", configuring helpdesk URL' . PHP_EOL;
    $config->set('helpdesk_url', getenv("OST_HELPDESK_URL"));
}

if (strlen(getenv("OST_HELPDESK_ONLINE") ?: '') > 0) {
    echo '"OST_HELPDESK_ONLINE=' . getenv("OST_HELPDESK_ONLINE") . '", configuring online status' . PHP_EOL;
    $isonline = 1 - (strtolower(getenv("OST_HELPDESK_ONLINE")) === "false");
    echo '-> set isonline to ' . $isonline . PHP_EOL;
    $config->set('isonline', $isonline);
}

/**
 * Set storage attachment backend if environment is configured
 */
if (strlen(getenv("OST_PLUGINS_STORAGEFS_PATH") ?: '') > 0) {
    echo '"OST_PLUGINS_STORAGEFS_PATH=' . getenv("OST_PLUGINS_STORAGEFS_PATH") . '", configuring storage' . PHP_EOL;

    $storageFsPlugin = PluginManager::getInstance(getenv("OST_PLUGINS_STORAGEFS_PLUGIN"));
    if ($storageFsPlugin === null) {
        echo '-> Installing "' . getenv("OST_PLUGINS_STORAGEFS_PLUGIN") . '"' . PHP_EOL;
        $pluginmanager = new PluginManager();
        $pluginmanager->install(getenv("OST_PLUGINS_STORAGEFS_PLUGIN"));
        PluginManager::clearCache();
        $storageFsPlugin = PluginManager::getInstance(getenv("OST_PLUGINS_STORAGEFS_PLUGIN"));
    }

    if (!$storageFsPlugin->isActive()) {
        $errors = [];
        $storageFsPlugin->update([
            'isactive' => true,
            'notes' => 'Automatically enabled by osticket-dockerized because "OST_PLUGINS_STORAGEFS_PATH" was set',
        ], $errors);
        if (count($errors) > 0) {
            print_r($errors);
            echo '-> Could not enable "' . getenv("OST_PLUGINS_STORAGEFS_PLUGIN") . '", exiting...' . PHP_EOL;
            exit(1);
        } else {
            echo '-> Enabled "' . getenv("OST_PLUGINS_STORAGEFS_PLUGIN") . '"' . PHP_EOL;
        }
    }

    if ($storageFsPlugin->getNumInstances() === 0) {
        $errors = [];
        $storageFsPlugin->addInstance([
            'name' => 'Attachments-fs ' . getenv("OST_PLUGINS_STORAGEFS_PATH"),
            'uploadpath' => getenv("OST_PLUGINS_STORAGEFS_PATH"),
            'isactive' => true,
        ], $errors);
        if (count($errors) > 0) {
            print_r($errors);
            echo '-> Could not configure instance of "' . getenv("OST_PLUGINS_STORAGEFS_PLUGIN") . '", exiting...' . PHP_EOL;
            exit(1);
        } else {
            echo '-> Configured instance of "' . getenv("OST_PLUGINS_STORAGEFS_PLUGIN") . '"' . PHP_EOL;
            PluginManager::clearCache();
        }

        // Make this new plugin the default attachment storage
        $storageFsPlugin->getActiveInstances()[0]->bootstrap();
        $storageChars = FileStorageBackend::allRegistered();
        $fileStorageBackendChar = array_search('FilesystemStorage', $storageChars);
        $config->set('default_storage_bk', $fileStorageBackendChar);
    }
}

// If we get here, setup was succesfull
touch("../setup/success");

?>