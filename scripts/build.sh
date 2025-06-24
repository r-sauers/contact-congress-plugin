#!/bin/bash

# This script builds all of the compiled files needed for the plugin to function, then zips the file.
cp bower_components/select2/dist/js/select2.min.js public/js/select2.min.js;
cp bower_components/select2/dist/css/select2.min.css public/css/select2.min.css;
npx wp-scripts build --webpack-copy-php --source-path=./blocks/src --output-path=./blocks/build;
npx wp-scripts plugin-zip
