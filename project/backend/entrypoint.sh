#!/bin/bash
# entrypoint.sh

# 1. Wait for the file system to be fully mounted (optional but safer)
sleep 1

# 3. Create the correct symbolic link *at runtime*.
# The correct link must be relative or absolute internal path.
ln -s /var/www/html/storage/app/public /var/www/html/public/storage

# 4. Execute the main container command (starting the web server)
exec "$@"
