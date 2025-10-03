#!/bin/bash

yarnbin="yarnpkg"

# assignation variable .env
unset RJSFILE && eval "$(cat .env | grep RJSFILE)" && test -n "$RJSFILE"
test -z "$RJSFILE" && echo "pas de variable RJSFILE dans .env" && exit 1;

test ! -f $1 &&	echo "pas de fichier pour $1"  && exit 1;

# création du fichier js compilé mais pas uglifié
$yarnbin run r.js -o $1 optimize=none out=src/js/$RJSFILE

test -z "$2" &&	echo "arg(2): uglifier avec terser ou google-closure-compiler" && exit 1;

case $2 in

    "terser")
	# uglification (minification du fichier js compilé précedemment)
	$yarnbin run terser -c -m -t --source-map -o src/js/${RJSFILE%.*}.min.js src/js/$RJSFILE
    ;;

    "google-closure-compiler")
	# pour le compilateur google-clojure-compiler :
	$yarnbin dlx google-closure-compiler --js=src/js/$RJSFILE --js_output_file=src/js/${RJSFILE%.*}.min.js
    ;;

    *)
	echo "uglifier avec terser ou google-closure-compiler"
	exit 1;
esac
