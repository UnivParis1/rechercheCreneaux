#!/bin/bash

case $1 in

	"optijs")
		echo bin/optimize.sh js/build.js terser;
		bin/optimize.sh js/build.js terser;
	;;

	"clear")

		if [ -z "$2" ]
		then
			echo "clear all / composer / yarn / jsmin";
			exit 2;
		fi

		case $2 in

			"all")
				echo "rm -rf vdor/ ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock";
				rm -rf vendor/ ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock;
			;;

			"yarn")
				rm -rf node_modules/ && rm -f yarn.lock;
			;;

			"composer")
				rm -rf vendor/ && rm composer.lock
			;;

			"jsmin")
				echo rm js/$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/);
				rm js/$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/);
			;;

			*)
				echo "clear all / composer / yarn / jsmin";
				exit 2;
			;;

		esac;
	;;
	
	"composerup")
		echo "rm composer.lock && rm -Ri vendor/ && composer update --ignore-platform-reqs";
		rm composer.lock;
		rm -Rif vendor/;
		composer update --ignore-platform-reqs;
	;;
	"yarnup")
		rm yarn.lock;
		rm -Rif .yarn;
		rm -Rif node_modules/;
		touch yarn.lock;
		yarn install;
	;;
	"syncvendor")
		test "$2" = "php-test" -o "$2" = "dagon" || { echo "spécifier la destination: php-test|dagon (test ou prod)"; exit 2; } ;

		hostsync=$2;

		rsync -av -e 'ssh -K ' vendor/ creneaux@"$hostsync":~/www/vendor/ ;
	;;
	"syncnode")
		test "$2" = "php-test" -o "$2" = "dagon" || { echo "spécifier la destination: php-test|dagon (test ou prod)"; exit 2; } ;

		hostsync=$2;

		rsync -av -e 'ssh -K ' node_modules/ creneaux@"$hostsync":~/www/node_modules ;

	;;
	*)
		echo "optijs|clearminjs|composerup|yarnup|syncvendor|syncnode" 1>&2 && exit 2;
	;;
esac
