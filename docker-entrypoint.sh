#!/bin/sh

# Create database etc. if it does not exist yet, but make sure to use our own configuration
if [ -d "/var/www/html/setup/" ]; then
    cp -rf /config/* /var/www/html/include
    chmod +w /var/www/html/include/ost-config.php
    cd /var/www/html/setup
    php ./envbasedinstall.php
    if [ $? -eq 0 ]; then
        cd ..
        rm -rf /var/www/html/setup
        chmod -w /var/www/html/include/ost-config.php
        chown -R www-data:www-data /var/www/attachments/
    else
        exit 1
    fi
fi


# Prepare scheduled tasks using cron
env >> /etc/environment
cron
chmod 644 /etc/cron.d/osticketcron

#launch the application
apache2-foreground
