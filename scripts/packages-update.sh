#!/bin/bash

# This script updates all of the dependencies.
npx wp-scripts packages-update;
composer update;
npm update;
bower update

# Everything below helps the user of the script ensure that the dependencies are wordpress complient.
jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[1].files as $files | .[0] | setpath(["files"]; $files)' package.json package.files.json | sponge package.json 

read -p "Would you like to continue and ensure that the dependencies don't break WordPress compliance? [y/n]" answer
if [ "$answer" = "n"];
then
    exit
fi

echo "Standard 1: A plugin must not consist of any files with whitespace as some filesystems do not support this."
echo "Standard 2: A plugin typically consists of files related to the plugin functionality (php, js, css, txt, md) and maybe some multimedia files (png, svg, jpg) and / or data files (json, xml)."
if [ -n "$(ls -A ./valid-dependency-extensions.tmp.json 2>/dev/null)" ]
then
    read -p "Would you like to use your saved valid extensions? [y/n]: " answer
    if [ "$answer" = "n" ];
    then
        echo '{}' > ./valid-dependency-extensions.tmp.json;
        echo -ne "\rIgnoring saved extensions!"
    else
        echo -ne "\rUsing saved extensions!"
    fi
else
    echo '{}' > ./valid-dependency-extensions.tmp.json;
fi

deps=$(jq --raw-output --slurp 'def getRootPackages: .[0] | .require | keys; def minifyDeps: reduce .[1].packages[] as $package ({}; . + {($package.name): $package.require | [keys[] | select(.!="php" and .!="php-64bit" and (. | test("^ext-.*") | not))]}); def getDeps: if (.unparsed | length) == 0 then . else {unparsed: (.unparsed - [.unparsed[0]] + .data[.unparsed[0]] - .parsed), parsed: (.parsed + [.unparsed[0]]), data: .data} | getDeps end; {unparsed: . | getRootPackages, parsed: [], data: . | minifyDeps } | getDeps | .parsed[]' composer.json composer.lock)

for dep in $deps
do
    full_path="./vendor/$dep/";
    echo "${dep} (Checking for whitespace in file names...)";
    readarray -d '' array < <(find ${full_path} -regextype 'egrep' -regex ".* .*" -print0);
    if [ ${#array[@]} -ne 0 ];
    then
        IFS=$'\n';
        files=($array);
        for file in $files
        do
            IFS='/';
            parts=($file)
            IFS=$'\n \t';
            grep -R "${parts[-1]}" "$full_path";
            if [ $? = 1 ];
            then
                echo "Moved $file to $(echo $file | tr ' ' '_')"
                sudo mv "$file" "$(echo $file | tr ' ' '_')"
            else
                read -p "Found references (see above), would you still like to rename the file? [y/n]: " answer
                if [ "$answer" = "y" ];
                then
                    echo "Moved $file to $(echo $file | tr ' ' '_')"
                    sudo mv "$file" "$(echo $file | tr ' ' '_')"
                fi
            fi
            IFS=$'\n';
        done
        IFS=$'\n \t';
    fi

    echo "${dep} (Checking for invalid file extensions...)";
    jq --slurp --argjson dep "\"$dep\"" 'if .[0] | has($dep) then .[0] else .[0] + {($dep): {"valid":[],"invalid":[]}} end' ./valid-dependency-extensions.tmp.json | sponge ./valid-dependency-extensions.tmp.json
    IFS=$'\n';
    exts=("$(find "$full_path" -type f | awk -F "/" '{ print $(NF) }' | awk -F "." '{ print $(NF) }' | sort -u)");
    for ext in $exts
    do
        IFS=$'\n \t';
        validExt=$(jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0][$dep].valid | any(.==$ext)' ./valid-dependency-extensions.tmp.json);
        invalidExt=$(jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0][$dep].invalid | any(.==$ext)' ./valid-dependency-extensions.tmp.json);
        if [ "$validExt" = "false" ] && [ "$invalidExt" = "false" ];
        then
            echo -n "Would you like to keep (k) files with the $ext extension or view (v) them [k/v]:"
            read answer
            if [ "$answer" = "k" ];
            then
                validExt="true"
                invalidExt="false"
                jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0] | setpath([$dep, "valid"]; .[$dep].valid + [$ext])' ./valid-dependency-extensions.tmp.json | sponge ./valid-dependency-extensions.tmp.json
            else
                find "$full_path" -type f -regex ".*$ext"
                echo -n "Would you like to keep (k) files with the $ext extension or delete (d) them [k/d]:"
                read answer
                if [ "$answer" = "k" ];
                then
                    validExt="true"
                    invalidExt="false"
                    jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0] | setpath([$dep, "valid"]; .[$dep].valid + [$ext])' ./valid-dependency-extensions.tmp.json | sponge ./valid-dependency-extensions.tmp.json
                else
                    validExt="false"
                    invalidExt="true"
                    jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0] | setpath([$dep, "invalid"]; .[$dep].invalid + [$ext])' ./valid-dependency-extensions.tmp.json | sponge ./valid-dependency-extensions.tmp.json
                fi
            fi
        fi

        if [ $validExt = "true" ];
        then
            jq --slurp --argjson dep "\"$dep\"" --argjson ext "\"$ext\"" '.[0] | setpath(["files"]; .files + ["vendor/" + $dep + "/**/*" + $ext, "vendor/" + $dep + "/*" + $ext])' package.json | sponge package.json
        fi
        IFS=$'\n';
    done
    IFS=$'\n \t';
done
echo -e "Done!"
