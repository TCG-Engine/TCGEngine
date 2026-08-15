#!/bin/bash
set -e

# Fix permissions on mounted code directory so PHP can write files. Tolerate individual
# file failures (e.g. a .git object mid-GC on the host) instead of aborting startup under
# `set -e` — a crash-looped container (restart: always retrying the same failure forever)
# is worse than skipping permissions on the handful of files that briefly vanished.
#
# Run it in the BACKGROUND. The mounted tree is ~33k files / ~2GB, so the walk costs ~4s
# on macOS and ~28s on a Windows bind mount, and all five *-web-server containers pay it
# concurrently on every start and restart. Apache does not need it to finish before it can
# serve, so exec immediately and let the chown catch up. Trade-off: for the first few
# seconds a request that writes to a not-yet-chowned file can fail — far cheaper than
# stalling every container's startup. The end state is identical either way.
{
    echo "Fixing permissions on /var/www/html/TCGEngine (background) ..."
    chown -R www-data:www-data /var/www/html/TCGEngine || true
    chmod -R u+rwX /var/www/html/TCGEngine || true
    echo "Permission fix complete."
} &

# Run original entrypoint or Apache
exec apache2-foreground
