#!/bin/bash

case $1 in
	"build")
		test -z "$2" && echo "build all / composer / node" && exit 2;
		case $2 in
			"all")
				./maintenance.sh build composer && ./maintenance.sh build node
			;;

			"composer")
				echo "rm composer.lock && rm -Ri vendor/ && composer update --ignore-platform-reqs";
				rm composer.lock;
				rm -Rif vendor/;
				composer update --ignore-platform-reqs;
			;;

			"node")
				rm yarn.lock;
				rm -Rif .yarn;
				rm -Rif node_modules/;
				touch yarn.lock;
				yarn install;
			;;
		esac
	;;
	"sync")
		test -z "$2" && echo "sync all / vendor / node" && exit 2;

		test -z "$3" && echo "pas de host mis pour 3ème argument" && exit 2;
		hostsync=$3;

		case $2 in
			"all")
				./maintenance.sh sync vendor $3 && ./maintenance.sh sync node $3
			;;
			"node")
				echo rsync -av -e 'ssh -K ' node_modules/ creneaux@"$hostsync":~/www/node_modules;
				rsync -av -e 'ssh -K ' node_modules/ creneaux@"$hostsync":~/www/node_modules;
			;;
			"vendor")
				echo rsync -av -e 'ssh -K ' vendor/ creneaux@"$hostsync":~/www/vendor/;
				rsync -av -e 'ssh -K ' vendor/ creneaux@"$hostsync":~/www/vendor/;
			;;
		esac
	;;

	"clear")

		if [ -z "$2" ]
		then
			echo "clear all / composer / node";
			exit 2;
		fi

		case $2 in

			"all")
				echo "rm -rf vdor/ ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock";
				rm -rf vendor/ ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock;
			;;

			"node")
				rm -rf node_modules/ && rm -f yarn.lock;
			;;

			"composer")
				rm -rf vendor/ && rm composer.lock
			;;
			*)
				echo "clear all / composer / node";
				exit 2;
			;;

		esac;
	;;

	"optijs")
		test -z "$2" && echo "optijs build / clear" && exit 2;

		case $2 in
			"build")
				./maintenance.sh optijs clear
				echo bin/optimize.sh js/build.js terser;
				bin/optimize.sh js/build.js terser;
			;;
			"clear")
				echo rm js/$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/);
				rm js/$(grep RJSFILE .env | cut -d= -f2 | sed s/\.js/\.\*/);
			;;
		esac;
	;;


	*)
		echo "build / sync / clear / optijs" 1>&2 && exit 1;
	;;
esac
