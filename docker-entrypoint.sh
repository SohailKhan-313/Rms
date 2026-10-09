#!/bin/bash

# Default port to 80 if PORT is not set by Railway
PORT="${PORT:-80}"
echo "==> [RMS] Starting RMS container on port: ${PORT}"

# 1. Resolve Railway AH00534 duplicate MPM error (strictly ensure only mpm_prefork is active)
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# 2. Configure Apache to listen on $PORT
cat <<EOF > /etc/apache2/ports.conf
Listen ${PORT}
EOF

# 3. Configure VirtualHost for $PORT with DocumentRoot and Alias /RMS
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

# 4. Enable site, environment passing, and test configuration
a2enmod env rewrite headers alias mpm_prefork >/dev/null 2>&1 || true

# Pass all cloud and database environment variables to Apache mod_php
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

# 5. Trigger DB migration in background after container starts
(
    sleep 3
    php /var/www/html/config/init_db.php 2>&1 || true
) &

echo "==> [RMS] Launching Apache Web Server on port ${PORT}..."
exec apache2-foreground
