#!/bin/bash
phpfile="$1"
idekey=idestart
mode=debug
testid=1
shift 1
XDEBUG_SESSION_START=idestart;FBCompareTestId=$testid XDEBUG_TRIGGER=$mode php -d'xdebug.remote_enable=1' -d'xdebug.remote_enable=1' -d'xdebug.remote_autostart=1' -d'xdebug.idekey='"$idekey" -f "$phpfile" -- "$@"

