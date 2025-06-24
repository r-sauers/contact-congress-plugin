#!/bin/bash

# This script finds the difference between the docker volume (./congress-volume), and the git directory (.)

diff -r . ./congress-volume \
    --exclude vendor \
    --exclude bower_components \
    --exclude docker \
    --exclude .git \
    --exclude .gitignore \
    --exclude congress.zip \
    --exclude node_modules \
    --exclude build;
