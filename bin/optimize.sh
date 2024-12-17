#!/bin/bash

# assignation variable .env
unset RJSFILE && eval "$(cat .env | grep RJSFILE)" && test -n "$RJSFILE"
test -z "$RJSFILE" && echo "pas de variable RJSFILE dans .env" && exit 1;

test ! -f $1 &&	echo "pas de fichier pour $1"  && exit 1;

# création du fichier js compilé mais pas uglifié
yarn run r.js -o $1 optimize=none out=js/$RJSFILE

test -z "$2" &&	echo "arg(2): uglifier avec terser ou google-closure-compiler" && exit 1;

case $2 in

    "terser")
	# uglification (minification du fichier js compilé précedemment)
	yarn run terser -c -m -t --source-map -o js/${RJSFILE%.*}.min.js js/$RJSFILE
    ;;

    "google-closure-compiler")
	# pour le compilateur google-clojure-compiler :
	yarn dlx google-closure-compiler --js=js/main-built.js --js_output_file=js/main-built.min.js
    ;;

    *)
	echo "uglifier avec terser ou google-closure-compiler"
	exit 1;
esac
