#!/bin/bash

# This script copies files from the git directory (.) to the docker volume (./congress-volume) with appropriate permissions.
sudo rsync -av --progress ./* ./congress-volume/ \
    --chown=www-data:www-data \
    --exclude node_modules \
    --exclude congress-volume \
    --exclude vendor \
    --exclude bower_components \
    --exclude docker \
    --exclude .git \
    --exclude .gitignore \
    --exclude congress.zip;
sudo setfacl -R -m \"u:$(whoami):rwx\" ./congress-volume/;
