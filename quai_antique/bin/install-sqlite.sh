#!/bin/bash

echo "=== Installing SQLite driver for PHP ==="

# Install the SQLite driver (which should always work)
sudo apt update
sudo apt install -y php8.3-sqlite3

echo "✅ SQLite driver installed"
echo "Now your Symfony application should work with SQLite database"
echo "To create the database, run: php bin/console doctrine:database:create"
