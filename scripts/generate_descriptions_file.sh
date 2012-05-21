#!/bin/sh

# This script reads all the DESCRIPTION files in all package directories, and
# puts this output into the docs/components_descriptions_marketing.txt file.

exec > docs/website/components_descriptions_marketing.txt

echo Overview
echo ========
echo
echo eZ Components is an enterprise ready, general purpose PHP components library. It is used independently or together for PHP application development. As a collection of high quality independent building blocks, eZ Components will both speed up development and reduce risks. An application can use one or more components effortlessly as they all adhere to the same naming conventions and follow the same structure. All components require__ atleast PHP 5.2.1.
echo
echo __ '/overview/requirements'
echo
echo

for i in trunk/*; do
	packagename=`echo $i | sed 's/trunk\///'`
	if test $packagename == 'autoload'; then
		continue;
	fi
	if test -f $i/DESCRIPTION; then
		echo $packagename
		php -r "echo str_repeat('-', strlen('$packagename'));"
		echo

		cat $i/DESCRIPTION
		echo
		echo Documentation__
		echo
		echo __ '/docs/api/latest/introduction_'$packagename.html

		echo
	fi
done

rst2xml docs/website/components_descriptions_marketing.txt > /tmp/desc.xml
xsltproc docs/rstxml2ezxml.xsl /tmp/desc.xml > docs/website/components_descriptions_marketing.ezxml
rm -rf /tmp/desc.xml
