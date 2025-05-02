#!/bin/bash

echo "=== Quai Antique Development Server ==="
echo "Starting PHP's built-in server for Symfony development with MySQL"
echo

# Function to check if port is available
is_port_available() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null ; then
        return 1
    else
        return 0
    fi
}

# Find an available port starting from 8000
PORT=8000
while ! is_port_available $PORT; do
    echo "Port $PORT is in use, trying next port..."
    PORT=$((PORT+1))
done

echo "Using port: $PORT"

# Ensure we're in the project root directory
PROJECT_DIR="/home/dlesieur/Documents/Studi/Quai-Antique/quai_antique"
cd "$PROJECT_DIR"

# Function to find the public directory
find_public_dir() {
    if [ -d "./public" ]; then
        echo "public"
    elif [ -d "./web" ]; then
        echo "web"
    else
        echo "Could not find public directory"
        exit 1
    fi
}

PUBLIC_DIR=$(find_public_dir)
echo "✅ Found public directory: $PUBLIC_DIR"

# Create a basic router file for PHP's built-in server if it doesn't exist
if [ ! -f "./server_router.php" ]; then
    echo "Creating router file for PHP's built-in server..."
    cat > "./server_router.php" << 'EOF'
<?php
// Built-in server router for Symfony
if (php_sapi_name() !== 'cli-server') {
    die("This script is only meant to be run with PHP's built-in web server");
}

// Static file check
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__ . '/public'; // or 'web' for older Symfony versions
$file = $publicDir . $uri;

if (is_file($file)) {
    // Serve the requested file as-is
    return false;
}

// Handle front controller
$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
require $publicDir . '/index.php';
EOF
    echo "✅ Created server router file"
fi

# Remove dev_index.php if it exists (we're using MySQL exclusively)
if [ -f "$PROJECT_DIR/public/dev_index.php" ]; then
    echo "Removing SQLite fallback controller..."
    rm "$PROJECT_DIR/public/dev_index.php"
fi

# Check if MySQL extension is available
if ! php -m | grep -q pdo_mysql; then
    echo "⚠️ WARNING: PDO MySQL extension is not available!"
    echo "This application is configured to use MySQL only."
    echo "Please install the PDO MySQL extension before continuing:"
    echo "sudo apt install php-mysql php8.3-mysql"
    exit 1
fi

echo "✅ PDO MySQL extension is available"
echo "✅ Using MySQL database configuration"

# Run the PHP development server
echo "Starting server at http://localhost:$PORT"
echo "CTRL+C to stop the server"

# Set up environment (but don't override DATABASE_URL)
export APP_ENV=dev
export APP_DEBUG=1

PHP_OPTIONS="-d display_errors=1 -d error_reporting=E_ALL"

# Start the server using the standard configuration
php $PHP_OPTIONS -S 0.0.0.0:$PORT -t public
