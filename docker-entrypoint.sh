#!/bin/bash
set -e

# Use PORT provided by host environment (default to 8000 if PORT is not set)
PORT="${PORT:-8000}"

echo "Configuring Apache to listen on port ${PORT}..."

# Dynamically update Apache ports configuration
if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf || true
fi

if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf || true
fi

echo "Starting Apache in foreground on port ${PORT}..."
exec apache2-foreground
