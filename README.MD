# osTicket-dockerized
This is a Docker container containing [osTicket](https://github.com/osTicket/osTicket).

Available for linux/amd64 and linux/arm64 as `rinkp/osticket-dockerized` and `ghcr.io/rinkp/osticket-dockerized`

An example [docker-compose.yml](https://raw.githubusercontent.com/rinkp/osticket-dockerized/main/docker-compose.yml) file is provided as well. This includes a local database instance, but you can also deploy the image with an external database server.<br/>
When using the local database instance, note that the first start will take an extra 30 seconds since MariaDB has to start.

Configuration happens through environment variables. These are listed below. Additionally, it is possible to store a `ost-config.php` file in `/config/ost-config.php` (e.g. file mount). 

| Variable name | Default | Description |
|---------------|---------|-------------|
| `OST_SECRET_SALT` | *None, mandatory* | 32-character random string (generate [here](https://passwordsgenerator.net/?length=32&symbols=0&numbers=1&lowercase=1&uppercase=1&similar=1&ambiguous=0&client=1&autoselect=0))|
| `OST_ADMIN_EMAIL` | *None, mandatory* | Email address used for database connection errors as well as the username for the first admin user |
| `OST_ADMIN_PASSWD` | *None, mandatory* | The initial password for the admin user |
| `OST_DBTYPE` | `mysql` | MySQL Database type |
| `OST_DBHOST` | `127.0.0.1:3306` | MySQL Database host |
| `OST_DBNAME` | `osticket` | MySQL Database name |
| `OST_DBUSER` | `root` | MySQL Database user |
| `OST_DBPASS` | ` ` | MySQL Database password |
| `OST_TABLE_PREFIX` | `ost_` | MySQL Database prefix |
| `OST_DBSSLCA` <br/>`OST_DBSSLCERT`<br/>`OST_DBSSLKEY`| *None, optional* | MySQL SSL settings (in case MySQL server requires certificate connections). Paths to files (e.g. in a mount on `/config`).
| `OST_MAIL_EOL` | `\n` | Change line ending in case your email server requires other line endings |
| `OST_TRUSTED_PROXIES` | ` ` | To detect IPs and protocols correctly in case osTicket is behind a reverse proxy (useful for lockouts and forcing HTTPS) |
| `OST_LOCAL_NETWORKS` | `127.0.0.0/24` | Whitelist authentication for these IPs |
| `OST_SESSION_SESSID` | `OSTSESSID` | Session name for session cookie |
| `OST_SESSION_BACKEND` <br/> `OST_MEMCACHE_SERVERS` | `db`<br/>` `| osTicket supports `memcache` as `SESSION_BACKEND` if the 'memcache' pecl extesion is installed. This also requires `MEMCACHE_SERVERS` to be configured as well.

Additionally, the container is configured to use 'opcache' for performance improvements. The settings for this can be modified using these environment variables.

| Variable name | Default | Description |
|---------------|---------|-------------|
| `PHP_OPCACHE_VALIDATE_TIMESTAMPS` | `0` | Whether to check if a PHP file has been changed before executing using the cache. If `0`, you have to restart the container after updates to PHP files. |
| `PHP_OPCACHE_MAX_ACCELERATED_FILES` | `16229` | The maximum number of files in the cache |
| `PHP_OPCACHE_MEMORY_CONSUMPTION` | `384` | The amount of memory (in MB) that can be used by 'opcache' |
| `PHP_OPCACHE_MAX_WASTED_PERCENTAGE` | `10` | The maximum percentage of wasted memory before 'opcache' is cleared |

## Using this repo

This repository contains 2 submodules, pull them using `git submodule update --init --recursive`

## Noteworthy changes
Tags containing `-patches` are built from a fork of osTicket: https://github.com/rinkp/osticket-dockerized. Updates are rebased from the original to allow for easy comparisons. See the changes for 1.18.1 [here](https://github.com/osTicket/osTicket/compare/1.18.x...rinkp:osTicket:v1.18.1-patches)

- Changes applied in the past:
  - Fix: Allowing multiple plugins with their own configuration (https://github.com/rinkp/osTicket/commit/3db0397135f799d33882596972e980cb46c90ac8), patched upstream in https://github.com/osTicket/osTicket/commit/3e96e5fdd649fcacc3a6cc2b177af47724fcd298
  - Fix: Session data (https://github.com/rinkp/osTicket/commit/cf8b8ce721223281bc98db54dc2271f6cfb6d6cd), included upstream in https://github.com/osTicket/osTicket/pull/6360
  - Fix: Strict attachment checking is too strict (https://github.com/rinkp/osTicket/commit/a3e1825a5b748aebcc41e485d1292e5e72c18b13 and https://github.com/rinkp/osTicket/commit/eda5af40fcfd2bbaff110bfebd95b0a800bc63f6), fixed upstream in https://github.com/osTicket/osTicket/commit/b5b9e2b4a6cce480853b673ca700316d9e063efc
- Current changes:
  - Allow certain forms to be visible when anonymous ticket creation is disabled (https://github.com/rinkp/osTicket/commit/2deed64e40a5a7738f0794ece9def6fa1c0fb20d)