#!/bin/sh

# Start the first process
/usr/sbin/php-fpm8.1 --nodaemonize &

# Start the second process
nginx -g "daemon off;" &

# Wait for any process to exit
wait

# Exit with status of process that exited first
exit $?
