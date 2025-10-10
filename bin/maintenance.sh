#!/bin/bash

yarnbin="yarnpkg"

test ! -f .env && echo "pas de fichier .env" && exit 1;

user=$(grep CAS_URI_APP .env | cut -d'=' -f2)
hostdir=$(grep HOSTDIR .env | cut -d'=' -f2)

test ! -n "$user" && echo "pas de variable CAS_URI_APP dans .env" && exit 1;
test ! -n "$hostdir" && echo "pas de variable HOSTDIR dans .env" && exit 1;

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
				$yarnbin run build
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
				echo rsync -av -e 'ssh -K ' node_modules/ "$user"@"$hostsync":"$hostdir"/node_modules;
				rsync -av -e 'ssh -K ' node_modules/ "$user"@"$hostsync":"$hostdir"/node_modules;
				rsync -av -e 'ssh -K ' public/build/ "$user"@"$hostsync":"$hostdir"/public/build/;
			;;
			"vendor")
				echo rsync -av -e 'ssh -K ' vendor/ "$user"@"$hostsync":"$hostdir"/vendor/;
				rsync -av -e 'ssh -K ' vendor/ "$user"@"$hostsync":"$hostdir"/vendor/;
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
				echo "rm -rf vendor/ ; rm -rf node_modules ; rm -f yarn.lock ; rm -f composer.lock";
				rm -rf vendor/ ; rm -rf node_modules/ ; rm -f yarn.lock ; rm -f composer.lock;
			;;

			"node")
				rm -rf node_modules/ && rm -f yarn.lock;
			;;

			"composer")
				rm composer.lock
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
		echo "build / sync/ clear" 1>&2 && exit 1;
	;;
esac
