<?php
/*********************************************************************
    ost-config.php

    Static osTicket configuration file. Mainly useful for mysql login info.
    Created during installation process and shouldn't change even on upgrades.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

#Disable direct access.
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)) || !defined('INCLUDE_DIR'))
    die('kwaheri rafiki!');

#Install flag
define('OSTINSTALLED',TRUE);
if(OSTINSTALLED!=TRUE){
    if(!file_exists(ROOT_DIR.'setup/install.php')) die('Error: Contact system admin.'); //Something is really wrong!
    //Invoke the installer.
    header('Location: '.ROOT_PATH.'setup/install.php');
    exit;
}

# Encrypt/Decrypt secret key - randomly generated during installation.
define('SECRET_SALT', getenv("OST_SECRET_SALT") ?: die("Missing required environment variable: OST_SECRET_SALT"));

#Default admin email. Used only on db connection issues and related alerts.
define('ADMIN_EMAIL', getenv("OST_ADMIN_EMAIL") ?: die("Missing required environment variable: OST_ADMIN_EMAIL"));

# Database Options
# ====================================================
# Mysql Login info
#
define('DBTYPE', getenv("OST_DBTYPE") ?: 'mysql');
define('DBHOST', getenv("OST_DBHOST") ?: '127.0.0.1:3306');
define('DBNAME', getenv("OST_DBNAME") ?: 'osticket');
define('DBUSER', getenv("OST_DBUSER") ?: 'root');
define('DBPASS', getenv("OST_DBPASS") ?: '');

# Table prefix
define('TABLE_PREFIX', getenv("OST_TABLE_PREFIX") ?: 'ost_');

#
# SSL Options
# ---------------------------------------------------
# SSL options for MySQL can be enabled by adding a certificate allowed by
# the database server here. To use SSL, you must have a client certificate
# signed by a CA (certificate authority). You can easily create this
# yourself with the EasyRSA suite. Give the public CA certificate, and both
# the public and private parts of your client certificate below.
#
# Once configured, you can ask MySQL to require the certificate for
# connections:
#
# > create user osticket;
# > grant all on osticket.* to osticket require subject '<subject>';
#
# More information (to-be) available in doc/security/hardening.md

$dbSslOptions = [
    'OST_DBSSLCA' => 'DBSSLCA',
    'OST_DBSSLCERT' => 'DBSSLCERT',
    'OST_DBSSLKEY' => 'DBSSLKEY'
];

foreach ($dbSslOptions as $env => $const) {
    $value = getenv($env);

    // Check for `null`-like values and set to `null` explicitly.
    if (
        false === $value
        || '' === $value
        || 'null' === $value
    ) {
        $value = null;
    }

    // While the `db_connect` function checks whether `DBSSLCA` is defined,
    // it is better to define all constants (even when they are `null`).
    define($const, $value);
}

#
# Mail Options
# ===================================================
# Option: MAIL_EOL (default: \n)
#
# Some mail setups do not handle emails with \r\n (CRLF) line endings for
# headers and base64 and quoted-response encoded bodies. This is an error
# and a violation of the internet mail RFCs. However, because this is also
# outside the control of both osTicket development and many server
# administrators, this option can be adjusted for your setup. Many folks who
# experience blank or garbled email from osTicket can adjust this setting to
# use "\n" (LF) instead of the CRLF default.
#
# References:
# http://www.faqs.org/rfcs/rfc2822.html
# https://github.com/osTicket/osTicket-1.8/issues/202
# https://github.com/osTicket/osTicket-1.8/issues/700
# https://github.com/osTicket/osTicket-1.8/issues/759
# https://github.com/osTicket/osTicket-1.8/issues/1217

define('MAIL_EOL', getenv("OST_MAIL_EOL") ?: "\n");

#
# HTTP Server Options
# ===================================================
# Option: ROOT_PATH (default: <auto detect>, fallback: /)
#
# If you have a strange HTTP server configuration and osTicket cannot
# discover the URL path of where your osTicket is installed, define
# ROOT_PATH here.
#
# The ROOT_PATH is the part of the URL used to access your osTicket
# helpdesk before the '/scp' part and after the hostname. For instance, for
# http://mycompany.com/support', the ROOT_PATH should be '/support/'
#
# ROOT_PATH *must* end with a forward-slash!

# define('ROOT_PATH', '/support/');


# Option: TRUSTED_PROXIES (default: <none>)
#
# To support running osTicket installation on a web servers that sit behind a
# load balancer, HTTP cache, or other intermediary (reverse) proxy; it's
# necessary to define trusted proxies to protect against forged http headers
#
# osTicket supports passing the following http headers from a trusted proxy;
# - HTTP_X_FORWARDED_FOR    =>  Chain of client's IPs
# - HTTP_X_FORWARDED_PROTO  =>  Client's HTTP protocal (http | https)
#
# You'll have to explicitly define comma separated IP addreseses or CIDR of
# upstream proxies to trust. Wildcard "*" (not recommended) can be used to
# trust all chained IPs as proxies in cases that ISP/host doesn't provide
# IPs of loadbalancers or proxies.
#
# References:
# http://en.wikipedia.org/wiki/X-Forwarded-For
#

define('TRUSTED_PROXIES', getenv("OST_TRUSTED_PROXIES") ?: '');


# Option: LOCAL_NETWORKS (default: 127.0.0.0/24)
#
# When running osTicket as part of a cluster it might become necessary to
# whitelist local/virtual networks that can bypass some authentication/checks.
#
# define comma separated IP addreseses or enter CIDR of local network.

define('LOCAL_NETWORKS', getenv("OST_LOCAL_NETWORKS") ?: '127.0.0.0/24');

#
# Session Options
# ===================================================
#
# Session Name (SESSID)
# ---------------------------------------------------
# Option: SESSION_SESSID (default: OSTSESID)
#
# osTicket Session Name (SESSID) - used to set session cookie
define('SESSION_SESSID', getenv("OST_SESSION_SESSID") ?: 'OSTSESSID');

# Session Storage Backends
# ---------------------------------------------------

# Option: SESSION_BACKEND (default: database)
#
# Values: 'database' (default)
#         'memcache' (Use Memcache servers)
#         'memcache.database' (Memcache Primary, Database Secondary)
#         'system' (use PHP settings as configured (not recommended!))
#
# osTicket supports Database by default as well as Memcache as a session
# storage backend if the `memcache` pecl extesion is installed. This also
# requires MEMCACHE_SERVERS to be configured as well.
#
# MEMCACHE_SERVERS can be defined as a comma-separated list of host:port
# specifications. If more than one server is listed, the session is written
# to all of the servers for redundancy.
#
if (!empty(getenv("OST_SESSION_BACKEND"))) define('SESSION_BACKEND', getenv("OST_SESSION_BACKEND"));
if (!empty(getenv("OST_MEMCACHE_SERVERS"))) define('MEMCACHE_SERVERS', getenv("MEMCACHE_SERVERS"));
?>
