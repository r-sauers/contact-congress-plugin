#!/bin/bash

# This file builds the files necessary for plugin function in dev mode, meaning
# the build files will update if any change is detected at the source.

cp bower_components/select2/dist/js/select2.min.js public/js/select2.min.js;
cp bower_components/select2/dist/css/select2.min.css public/css/select2.min.css;
npx wp-scripts start \
    --webpack-copy-php \
    --source-path=./blocks/src \
    --output-path=./blocks/build;
