#!/bin/bash

if test $# != 3; then
	echo "Usage: ./scripts/release2.sh [component] [baseversion] [version]";
	exit;
fi

componentRaw=$1
baseversion=$2
version=$3
echo

echo -n "* Figuring out whether this is stable or trunk: "
component=`echo $componentRaw | cut -d / -f 1`
componentRelease=`echo $componentRaw | cut -d / -f 2`
if test $component == $componentRelease; then
	echo "trunk"
	branch=trunk
else
	echo "stable/$componentRelease"
	branch=stable
fi

echo "* Copying to release branch"
if test -d releases/$component/$version; then
	echo "  - Directory already exists, aborting."
	exit;
fi

if test $branch == 'trunk'; then
	svn cp trunk/$component releases/$component/$version
else
	svn cp stable/$component/$componentRelease releases/$component/$version
fi

echo "* Committing component to SVN"
if test $branch == 'trunk'; then
	svn commit -m "- Copying $component to version $version" releases/$component/$version
else
	svn commit -m "- Copying $component($componentRelease) to version $version" releases/$component/$version
fi

cd releases/$component/$version
../../../scripts/autogen-version-tags.sh
cd ../../..

echo "* Updating release-info/latest"
cat release-info/latest | sed "s/$component:.*/$component: $version/" > /tmp/release-info
mv /tmp/release-info release-info/latest

echo "* Committing component to SVN"
if test $branch == 'trunk'; then
	svn commit -m "- Released $component version $version" trunk/$component releases/$component/$version release-info/latest
else
	svn commit -m "- Released $component($componentRelease) version $version" trunk/$component stable/$component/$componentRelease releases/$component/$version release-info/latest
fi

echo "* Creating PEAR package"
php -derror_reporting=0 scripts/create_pear_package.php -v $version -b $baseversion -p $component

echo
echo "All clear"
