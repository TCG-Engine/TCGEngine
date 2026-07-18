#!/bin/bash
set -e

# Fix permissions on mounted code directory so PHP can write files. Tolerate individual
# file failures (e.g. a .git object mid-GC on the host) instead of aborting startup under
# `set -e` — a crash-looped container (restart: always retrying the same failure forever)
# is worse than skipping permissions on the handful of files that briefly vanished.
echo "Fixing permissions on /var/www/html/TCGEngine ..."
chown -R www-data:www-data /var/www/html/TCGEngine || true
chmod -R u+rwX /var/www/html/TCGEngine || true

# Run original entrypoint or Apache
exec apache2-foreground
