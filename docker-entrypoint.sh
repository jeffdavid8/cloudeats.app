#!/bin/sh
set -eu

PORT="${PORT:-8080}"

echo "Configuring Apache to listen on port ${PORT}"

# Replace the default Apache Listen directive with the Cloud Run port.
if [ -f /etc/apache2/ports.conf ]; then
  sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
fi

if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf
fi

exec apache2-foreground
