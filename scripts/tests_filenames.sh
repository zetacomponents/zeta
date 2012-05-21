#!/bin/bash
my_mv="$1"
component="$2"

if [ -z "$my_mv" ]; then
    echo This script finds test files which are not suffixed with _test.php
    echo and it can fix it.
    echo 
    echo Arguments: 
    echo scripts/tests_filenames.sh 'MV COMMAND' [component]
    echo 
    echo Usages:
    echo scripts/tests_filenames.sh 'echo'
    echo scripts/tests_filenames.sh 'mv'
    echo scripts/tests_filenames.sh 'svn mv'
    echo
    echo Optionnaly, suffix the command with a component name to work with only
    echo one component
    exit 1
fi

echo MV Command: $my_mv
if [ -n "$component" ]; then
    echo Component: $component
fi

for c in trunk/*; do
    if [ ! -d "$c" ]; then
        continue
    fi

    if [ -n "$component" -a "${c##trunk/}" != "$component" ]; then
        continue
    fi

    for f in $(find $c/tests -name '*php' -not -iwholename '*.svn*' -not -iwholename '*_test.php'); do
        grep -q '^class .* extends ezcTestCase' $f
        if [ $? -eq 0 ]; then
            if [[ $f == *_tests.php ]]; then
                $my_mv $f ${f%_tests.php}_test.php
            else
                $my_mv $f ${f%.php}_test.php
            fi
        fi
    done
done

