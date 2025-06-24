#!/bin/bash

# This script copies files from the docker volume (./congress-volume) to the git directory (.) with appropriate permissions.
rsync -av --progress ./congress-volume/* . \
    --exclude node_modules \
    --exclude vendor \
    --exclude bower_components \
    --exclude .git \
    --exclude .gitignore;
