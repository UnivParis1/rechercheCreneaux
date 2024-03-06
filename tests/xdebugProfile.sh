#!/bin/bash
phpfile="$1"
idekey=idestart
mode=debug
shift 1
XDEBUG_SESSION_START=idestart;XDEBUG_TRIGGER=$mode php -d'xdebug.remote_enable=1' -d'xdebug.remote_enable=1' -d'xdebug.remote_autostart=1' -d'xdebug.idekey='"$idekey" -f "$phpfile" -- "$@"

