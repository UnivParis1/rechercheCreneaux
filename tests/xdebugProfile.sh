#!/bin/bash

phpfile="$1"
idekey="idestart"
mode="debug"
testid="1"

if [ $mode == "profile" ] || [ $mode == "debug" ]; then
    XDEBUG_SESSION_START=$idekey FBCompareTestId=$testid XDEBUG_TRIGGER=$mode php -d'xdebug.remote_enable=1' -d'xdebug.remote_enable=1' -d'xdebug.remote_autostart=1' -d"xdebug.idekey=$idekey" -f "$phpfile" "$2"
fi
