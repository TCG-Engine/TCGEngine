#!/bin/bash
set -e

# Fix permissions on mounted code directory so PHP can write files
echo "Fixing permissions on /var/www/html/TCGEngine ..."
chown -R www-data:www-data /var/www/html/TCGEngine
chmod -R u+rwX /var/www/html/TCGEngine

# Run original entrypoint or Apache
exec apache2-foreground
