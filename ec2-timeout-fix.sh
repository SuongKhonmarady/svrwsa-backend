#!/bin/bash
# EC2 Server Configuration Commands

echo "=== Fixing 504 Gateway Timeout Issues ==="

# 1. Edit Nginx configuration
echo "1. Updating Nginx configuration..."
sudo nano /etc/nginx/sites-available/default
# OR if you have a custom site config:
# sudo nano /etc/nginx/sites-available/your-site-name

echo "Add these lines to your server block:"
echo "    proxy_read_timeout 120s;"
echo "    proxy_connect_timeout 120s;"
echo "    proxy_send_timeout 120s;"
echo "    fastcgi_read_timeout 120s;"
echo "    fastcgi_connect_timeout 120s;"
echo "    fastcgi_send_timeout 120s;"
echo "    client_body_timeout 120s;"
echo "    client_header_timeout 120s;"

# 2. Edit PHP-FPM configuration
echo "2. Updating PHP-FPM configuration..."
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
# Note: Replace 8.2 with your PHP version (could be 8.1, 8.3, etc.)

echo "Add these lines to the [www] section:"
echo "    request_terminate_timeout = 120s"
echo "    php_admin_value[max_execution_time] = 120"
echo "    php_admin_value[memory_limit] = 256M"

# 3. Test and restart services
echo "3. Testing configurations and restarting services..."
echo "sudo nginx -t"
echo "sudo systemctl restart nginx"
echo "sudo systemctl restart php8.2-fpm"  # Replace 8.2 with your PHP version

# 4. Check status
echo "4. Check service status:"
echo "sudo systemctl status nginx"
echo "sudo systemctl status php8.2-fpm"

echo "=== Configuration complete! ==="
