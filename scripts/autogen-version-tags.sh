#!/bin/sh

# Usage:
#   You should run this from *within* the
#   releases/<packagename>/<version> directory.

fullVersion=`pwd | sed 's/.*\///g'`
minorVersion=`pwd | sed 's/.*\///g' | sed 's/^\([0-9]\+\.[0-9]\+\).*/\1/'`
comp=`pwd | cut -d / -f 7`

for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogentag\/\//$fullVersion/g" $i; done
for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogen\/\//$fullVersion/g" $i; done

date=`date +"%A %d %B %Y"`
perl -p -i -e "s/$fullVersion\ \-\ \[RELEASEDATE\]/$fullVersion - $date/" \
	ChangeLog \
	../../../trunk/$comp/ChangeLog \
	../../../stable/$comp/$minorVersion/ChangeLog
