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

# Pass all environment variables to Apache mod_php
echo "# RMS Environment Variables for Apache" > /etc/apache2/conf-available/docker-env.conf
while IFS='=' read -r name value; do
    if [[ ! -z "$name" && "$name" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; then
        escaped_val=$(printf '%s\n' "$value" | sed 's/\\/\\\\/g; s/"/\\"/g')
        echo "SetEnv \"$name\" \"$escaped_val\"" >> /etc/apache2/conf-available/docker-env.conf
        echo "PassEnv $name" >> /etc/apache2/conf-available/docker-env.conf
    fi
done < <(env)
a2enconf docker-env >/dev/null 2>&1 || true

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
    @file_put_contents("/var/www/html/config/.db_env.json", json_encode($env));
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
