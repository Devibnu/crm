#!/bin/sh
export PHPRC="/opt/homebrew/etc/php/8.4"
export PHP_FCGI_CHILDREN=0
export PHP_FCGI_MAX_REQUESTS=1000
export REDIRECT_STATUS=200
if [ -n "$PATH_TRANSLATED" ]; then
    export SCRIPT_FILENAME="$PATH_TRANSLATED"
fi
exec /opt/homebrew/bin/php-cgi
