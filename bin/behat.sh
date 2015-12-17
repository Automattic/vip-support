#!/usr/bin/env bash

# Start the PHP web server, and capture the PID so we can clean up
php -S localhost:8000 -t vendor/wordpress -d disable_functions=mail &
PHP_SERVER_PID=$!

# Run Behat tests
./bin/behat
BEHAT_EXIT_CODE=$?

# Kill the PHP web server we started
kill $PHP_SERVER_PID

exit $BEHAT_EXIT_CODE
