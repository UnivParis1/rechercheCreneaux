#!/bin/bash

yarnbin="yarnpkg"

test ! -d bin/ && test ! -d src/ && test ! -d vendor/ && echo "pas de repertoire bin/ ou src/ ou vendor/ ce script doit être appelé à la racine du projet" && exit 1;

case $1 in
	"build")
		test -z "$2" && echo "build all / composer / node" && exit 2;
		case $2 in
			"all")
				bin/maintenance.sh build composer && bin/maintenance.sh build node
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
				$yarnbin install;
				ln -s ../node_modules src/
			;;
		esac
	;;
	"sync")
		test -z "$2" && echo "sync all / vendor / node" && exit 2;

		test -z "$3" && echo "pas de host mis pour 3ème argument" && exit 2;
		hostsync=$3;

		case $2 in
			"all")
				bin/maintenance.sh sync vendor $3 && bin/maintenance.sh sync node $3
			;;
			"node")
				echo rsync -av -e 'ssh -K ' node_modules/ creneau@"$hostsync":~/www/node_modules;
				rsync -av -e 'ssh -K ' node_modules/ creneau@"$hostsync":~/www/node_modules;
			;;
			"vendor")
				echo rsync -av -e 'ssh -K ' vendor/ creneau@"$hostsync":~/www/vendor/;
				rsync -av -e 'ssh -K ' vendor/ creneau@"$hostsync":~/www/vendor/;
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
				echo "rm -rf vendor/ ; rm src/node_modules ; rm -rf node_modules ; rm -f yarn.lock ; rm -f composer.lock";
				rm -rf vendor/ ; rm src/node_modules ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock;
			;;

			"node")
				rm src/node_modules ; rm -rf node_modules/ && rm -f yarn.lock;
			;;

			"composer")
				rm -rf src/vendor/ && rm composer.lock
			;;
			*)
				echo "clear all / composer / node";
				exit 2;
			;;

		esac;
	;;

	*)
		echo "build / clear" 1>&2 && exit 1;
	;;
esac
