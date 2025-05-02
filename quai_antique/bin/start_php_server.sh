#!/bin/bash
# Start PHP's built-in server as an alternative to symfony server
echo "Starting PHP built-in web server on http://localhost:8000"
echo "Press Ctrl+C to stop the server"
cd "$(dirname "$0")"
php -S 0.0.0.0:8000 -t public server.php
