#!/bin/bash

# assignation variable .env
unset RJSFILE && eval "$(cat .env | grep RJSFILE)" && test -n "$RJSFILE"
test -z "$RJSFILE" && echo "pas de variable RJSFILE dans .env" && exit 1;

# création du fichier js compilé mais pas uglifié
yarn run r.js -o js/build.js optimize=none out=js/$RJSFILE

# uglification (minification du fichier js compilé précedemment)
node_modules/terser/bin/terser -c -m --source-map --output js/${RJSFILE%.*}.min.js js/$RJSFILE
