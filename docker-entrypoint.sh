#!/bin/bash

# Default port to 80 if PORT is not set by Railway
PORT="${PORT:-80}"
echo "==> [RMS] Starting RMS container on port: ${PORT}"

# 1. Configure Apache to listen on $PORT and port 80 simultaneously
if [ "$PORT" != "80" ]; then
    cat <<EOF > /etc/apache2/ports.conf
Listen ${PORT}
Listen 80
EOF
else
    echo "Listen 80" > /etc/apache2/ports.conf
fi

# 2. Configure VirtualHost for $PORT and 80 with DocumentRoot and Alias /RMS
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT} *:80>
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

# 3. Enable site
a2ensite 000-default.conf >/dev/null 2>&1

# 4. Trigger DB migration in background after container starts
(
    sleep 3
    php /var/www/html/config/init_db.php 2>&1 || true
) &

echo "==> [RMS] Launching Apache Web Server..."
exec apache2-foreground
