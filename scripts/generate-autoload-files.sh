#!/bin/sh

for i in trunk/*; do
	componentname=`echo $i | sed 's/trunk\///'`
	if test "$componentname" == 'run-tests-tmp' -o "$componentname" == 'autoload';
	then
		continue;
	fi
	echo "Generating autoload files for $componentname"
	php -derror_reporting=E_ALL scripts/generate-autoload-file.php -c $componentname -t trunk/$componentname
done
