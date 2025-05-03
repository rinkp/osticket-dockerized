#!/bin/sh

set -uo pipefail

# Verify that the attachments directory is a volume (to prevent dataloss)
if [ ! -z "$OST_PLUGINS_STORAGEFS_PATH" ]; then
    cat /etc/mtab | grep " $OST_PLUGINS_STORAGEFS_PATH " > /dev/null
    if [ $? -ne 0 ]; then
        echo "\$OST_PLUGINS_STORAGEFS_PATH=\"$OST_PLUGINS_STORAGEFS_PATH\", but \"$OST_PLUGINS_STORAGEFS_PATH\" is not a volume"
        exit 1
    fi
fi

# If we already have a config file, simply drop setup folder
if [ -f "/var/www/localhost/htdocs/include/ost-config.php" ]; then
    rm -rf /var/www/localhost/htdocs/setup
fi

# Create database etc. if it does not exist yet, but make sure to use our own configuration
if [ -d "/var/www/localhost/htdocs/setup/" -a ! -f "/var/www/localhost/htdocs/include/ost-config.php" ]; then
    cp -rf /config/* /var/www/localhost/htdocs/include
    chmod +w /var/www/localhost/htdocs/include/ost-config.php
    cd /var/www/localhost/htdocs/setup
    php ./envbasedinstall.php
    if [ -f "/var/www/localhost/htdocs/setup/success" ]; then
        cd ..
        rm -rf /var/www/localhost/htdocs/setup
        chmod 0644 /var/www/localhost/htdocs/include/ost-config.php
        chown -R apache:apache $OST_PLUGINS_STORAGEFS_PATH
    else
        # Undo setting the configuration
        rm -f /var/www/localhost/htdocs/include/ost-config.php
        exit 1
    fi
fi

if [ ! -z "$OST_HELPDESK_URL" ]; then
    echo -n "\$OST_HELPDESK_URL=\"$OST_HELPDESK_URL\", setting Apache servername: "
    echo "ServerName ${OST_HELPDESK_URL}" | sed -E "s/https?:\/\///g" | tee /etc/apache2/conf.d/servername.conf
fi

# Prepare scheduled tasks using cron
env | grep -E '^(OST|PHP).*' >> /etc/environment
crond
chmod 0644 /etc/cron.d/osticketcron

#launch the application
httpd -DFOREGROUND
