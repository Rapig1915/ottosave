#!/bin/sh

echo "Running dym-node-container's entrypoint file..."

echo "Modifying user (hack for mac)..."
usermod -u 1000 www-data #a hack for macs

echo "dym-node-container is ready!"
npm run watch-poll