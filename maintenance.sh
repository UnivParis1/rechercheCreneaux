#!/bin/bash

case $1 in

	"optijs")
		echo bin/optimize.sh js/build.js terser;
		bin/optimize.sh js/build.js terser;
	;;

	"clearminjs")
		echo rm js/`cat .env | grep RJSFILE | cut -d= -f2 | sed s/\.js/\.\*/`;
		rm js/`cat .env | grep RJSFILE | cut -d= -f2 | sed s/\.js/\.\*/`;
	;;

	*)
		echo "optijs|clearminjs"
	;;
esac
