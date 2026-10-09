#!/bin/bash
set -e

# Default port to 80 if PORT is not set by Railway
PORT="${PORT:-80}"
echo "==> [RMS] Starting RMS container on port: ${PORT}"

# 1. Start embedded MariaDB if no external MySQL host or URL is provided
has_external_db=0
if [ -n "$MYSQL_URL" ] || [ -n "$DATABASE_URL" ] || [ -n "$MYSQLHOST" ] || [ -n "$DB_HOST" ]; then
    has_external_db=1
    echo "==> [RMS] External database environment detected (${MYSQLHOST:-$DB_HOST:-$MYSQL_URL}). Skipping embedded MariaDB."
fi

if [ "$has_external_db" -eq 0 ]; then
    echo "==> [RMS] No external database configured. Starting embedded MariaDB on 127.0.0.1:3306..."
    mkdir -p /var/run/mysqld /var/lib/mysql /var/log/mysql
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql
    chmod 777 /var/run/mysqld

    # Initialize data directory if first run
    if [ ! -d "/var/lib/mysql/mysql" ]; then
        echo "==> [RMS] Initializing MariaDB data directory..."
        mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || true
    fi

    # Launch mysqld daemon in background
    echo "==> [RMS] Starting mysqld_safe daemon..."
    /usr/bin/mysqld_safe --user=mysql --skip-name-resolve >/var/log/mysql/mysqld.log 2>&1 &

    # Wait up to 25 seconds for MariaDB to become ready
    echo "==> [RMS] Waiting for embedded MariaDB to accept connections..."
    db_ready=0
    for i in $(seq 1 25); do
        if mysqladmin ping --silent 2>/dev/null || mysqladmin -h 127.0.0.1 ping --silent 2>/dev/null; then
            db_ready=1
            echo "==> [RMS] Embedded MariaDB is UP and ACCEPTING CONNECTIONS! (ready after ${i}s)"
            break
        fi
        sleep 1
    done

    if [ "$db_ready" -eq 1 ]; then
        # Ensure database 'rms' exists and root has full privileges locally and via 127.0.0.1
        mysql -e "CREATE DATABASE IF NOT EXISTS \`rms\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
        mysql -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null || true

        # Default environment variables for Apache and PHP
        export MYSQLHOST="127.0.0.1"
        export MYSQLPORT="3306"
        export MYSQLUSER="root"
        export MYSQLPASSWORD=""
        export MYSQLDATABASE="rms"
        export MYSQL_URL="mysql://root@127.0.0.1:3306/rms"
        echo "==> [RMS] Embedded MariaDB credentials exported successfully."
    else
        echo "==> [RMS] Warning: MariaDB start timed out. Continuing startup..."
    fi
fi

# 2. Resolve Railway AH00534 duplicate MPM error (strictly ensure only mpm_prefork is active)
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# 3. Configure Apache to listen on $PORT
cat <<EOF > /etc/apache2/ports.conf
Listen ${PORT}
EOF

# 4. Configure VirtualHost for $PORT with DocumentRoot and Alias /RMS
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    UseCanonicalName Off
    UseCanonicalPhysicalPort Off

    # Route /RMS/... paths seamlessly to /var/www/html/...
    Alias /RMS /var/www/html

    <Directory /var/www/html>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# 5. Enable site, environment passing, and test configuration
a2enmod env rewrite headers alias mpm_prefork >/dev/null 2>&1 || true

cat <<'EOF' > /etc/apache2/conf-available/docker-env.conf
# Pass Docker container environment variables to Apache mod_php
PassEnv MYSQLHOST MYSQLPORT MYSQLUSER MYSQLPASSWORD MYSQLDATABASE
PassEnv MYSQL_URL DATABASE_URL MYSQL_PUBLIC_URL DATABASE_PUBLIC_URL MYSQL_PRIVATE_URL
PassEnv MYSQL_HOST MYSQL_PORT MYSQL_USER MYSQL_PASSWORD MYSQL_DATABASE
PassEnv DB_HOST DB_PORT DB_USER DB_PASSWORD DB_NAME DB_HOSTNAME DB_USERNAME DB_PASS
PassEnv PORT RAILWAY_ENVIRONMENT RAILWAY_SERVICE_NAME
EOF
a2enconf docker-env >/dev/null 2>&1 || true

# Append variables to Apache envvars so Apache worker inherits them
for k in MYSQLHOST MYSQLPORT MYSQLUSER MYSQLPASSWORD MYSQLDATABASE MYSQL_URL DATABASE_URL MYSQL_PUBLIC_URL DATABASE_PUBLIC_URL MYSQL_PRIVATE_URL MYSQL_HOST MYSQL_PORT MYSQL_USER MYSQL_PASSWORD MYSQL_DATABASE DB_HOST DB_PORT DB_USER DB_PASSWORD DB_NAME PORT; do
    val="${!k}"
    if [ -n "$val" ]; then
        echo "export $k=\"$val\"" >> /etc/apache2/envvars
    fi
done

# Dump database variables to JSON for guaranteed PHP runtime fallback
php -r '
    $env = [];
    $keys = [
        "MYSQL_URL","DATABASE_URL","MYSQL_PUBLIC_URL","DATABASE_PUBLIC_URL","MYSQL_PRIVATE_URL",
        "MYSQLHOST","MYSQL_HOST","DB_HOST","DB_HOSTNAME","MYSQL_HOSTNAME",
        "MYSQLPORT","MYSQL_PORT","DB_PORT",
        "MYSQLUSER","MYSQL_USER","DB_USER","DB_USERNAME","MYSQL_USERNAME",
        "MYSQLPASSWORD","MYSQL_PASSWORD","DB_PASSWORD","DB_PASS","MYSQL_ROOT_PASSWORD",
        "MYSQLDATABASE","MYSQL_DATABASE","DB_NAME","DB_DATABASE","PORT"
    ];
    foreach ($keys as $k) {
        $v = getenv($k);
        if ($v !== false && $v !== "") $env[$k] = $v;
    }
    @file_put_contents("/var/www/html/config/.db_env.json", json_encode($env, JSON_PRETTY_PRINT));
    @chmod("/var/www/html/config/.db_env.json", 0666);
' 2>/dev/null || true

a2ensite 000-default.conf >/dev/null 2>&1
apache2ctl configtest || true

# 6. Trigger DB migration & schema seeder in background
(
    sleep 2
    php /var/www/html/config/init_db.php 2>&1 || true
) &

echo "==> [RMS] Launching Apache Web Server on port ${PORT}..."
exec apache2-foreground
