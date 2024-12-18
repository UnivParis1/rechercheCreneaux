#!/bin/bash

case $1 in

	"optijs")
		echo bin/optimize.sh js/build.js terser;
		bin/optimize.sh js/build.js terser;
	;;

	"clearminjs")
		# shellcheck disable=SC2046
		echo rm js/$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/);
		rm js/"$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/)";
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
