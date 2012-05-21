#!/bin/sh

for i in `find . -name \*.php`; do
	tail=`tail -n 1 $i`;
	if test "$tail" != "?>" -a "$tail" != "</html>"; then
		echo -n $i ""
	fi
done
echo
