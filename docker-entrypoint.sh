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

# 4. Enable site and test configuration
a2ensite 000-default.conf >/dev/null 2>&1
apache2ctl configtest || true

# 5. Trigger DB migration in background after container starts
(
    sleep 3
    php /var/www/html/config/init_db.php 2>&1 || true
) &

echo "==> [RMS] Launching Apache Web Server on port ${PORT}..."
exec apache2-foreground
