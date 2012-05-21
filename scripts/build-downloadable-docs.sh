#!/bin/sh

if test $# -lt 1; then
	echo "Usage: scripts/build-docs.sh <version> ..."
	exit 0;
fi

release=$1

BASE_OUTPUT_DIR=/home/httpd/html/components
DOC_OUTPUT_DIR=${BASE_OUTPUT_DIR}/phpdoc_gen/ezcomponents-$release
HTTP_ROOT_DIR=/components/phpdoc_gen/ezcomponents-$release

wd=`pwd`

rm -rf ${DOC_OUTPUT_DIR} || exit 6

mkdir -p ${DOC_OUTPUT_DIR}
ln -s /home/httpd/html/components/design ${DOC_OUTPUT_DIR}/design

echo "Copying overview"
cp docs/overview.tpl ${DOC_OUTPUT_DIR} || exit 12

echo "Preparing top left_menu_comp.tpl"
cat > ${DOC_OUTPUT_DIR}/left_menu_comp.html << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ Components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="http://ezcomponents.org/docs/install">Installation Guide</a></li>
<li><a href="${HTTP_ROOT_DIR}/tutorials.html">Tutorials</a></li>
</ul>

<h2>Versions</h2>
<ul>
EOF

mkdir -p ${DOC_OUTPUT_DIR}

echo "Update main index file"
cat >> ${DOC_OUTPUT_DIR}/left_menu_comp.html << EOF
<li><a href="${HTTP_ROOT_DIR}/">eZ Components $release</a></li>
EOF

echo "Writing config file for $release"
cd $wd
php-5.1dev scripts/build-php-doc-config.php $release $release off > /tmp/doc-components.ini || exit 1

j=`php scripts/list-export-dirs.php $release`

cd /home/httpd || exit 2

cd ezcomponents || exit 4
mkdir -p ${DOC_OUTPUT_DIR} || exit 8

echo "Copying overview for $release"
cp docs/overview_$release.tpl ${DOC_OUTPUT_DIR}

echo "Running php documentor for $release"
php-5.1dev /usr/local/bin/phpdoc -q on -c /tmp/doc-components.ini >/tmp/docbuild-$release.log 2>&1 || exit 8
./scripts/setup-env.sh

echo "Writing left_menu_comp_$release.tpl"
cat > ${DOC_OUTPUT_DIR}/left_menu_comp.html << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ Components $release</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="http://ezcomponents.org/docs/install">Installation Guide</a></li>
<li><a href="${HTTP_ROOT_DIR}/tutorials.html">Tutorials</a></li>
</ul>

<h2>Components</h2>
<ul>
EOF


cat > ${DOC_OUTPUT_DIR}/index.php << EOF
<?php
include '../overview_$release.tpl';
?>
EOF


echo "Generating Tutorials for $release:"
echo "* Tutorials overview page start"

cat >> ${DOC_OUTPUT_DIR}/tutorials.tpl <<EOF
<div class="attribute-heading"><h1>Tutorials</h1></div>
<ul>
EOF

cp ${DOC_OUTPUT_DIR}/tutorials.tpl ${DOC_OUTPUT_DIR}/tutorials.html

for i in $j; do
	comp=`echo $i | cut -d / -f 2`
	version=`echo "$i" | sed "s/\/$comp//" | sed "s/releases\///"`
	if test -f $i/docs/tutorial.txt; then
		echo "* $comp ($version)"
		php scripts/render-tutorial.php -c $comp -t ${DOC_OUTPUT_DIR} -v $version -r $release

		cp ${DOC_OUTPUT_DIR}/introduction_$comp.html /tmp/file4.html
		php -r "echo preg_replace( '@/docs/api/(.*?)/@', '', file_get_contents( '/tmp/file4.html' ) ); " > ${DOC_OUTPUT_DIR}/introduction_$comp.html

		cat >> ${DOC_OUTPUT_DIR}/tutorials.html << EOF
<li><a href="introduction_$comp.html">$comp</a></li>
EOF

# Add changelog
		php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $i/ChangeLog -o "changelog_$comp.html"
		php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $i/CREDITS -o "credits_$comp.html"

# Add extra docs for tutorials
		extra1=""
		extra2=""
		for t in $i/docs/*.txt; do
			output_name=`echo $t | cut -d / -f 5 | sed 's/.txt/.html/'`;
			if test $output_name != "tutorial.html"; then
				if test $output_name != "docs"; then
					echo -n "  - Rendering extra doc '$output_name' to ${comp}_${output_name}"
					php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $t
					short_name=`echo $output_name | sed 's/.html//'`
					short_name=`php -r "echo ucfirst( '$short_name' );"`
					extra1="$extra1 <b>[ <a href='../${comp}_${output_name}'>$short_name</a> ]</b>"
					extra2="$extra2 <b>[ <a href='${comp}_${output_name}'>$short_name</a> ]</b>"
				fi
			fi
		done

		if test "$extra1" != ""; then
			for w in ${DOC_OUTPUT_DIR}/$comp/*.html; do
				echo "- Postprocessing $w"
				cp $w /tmp/file2.html
				php -r "echo preg_replace( '@/docs/api/(.*?)/@', '', file_get_contents( '/tmp/file2.html' ) ); " > /tmp/file3.html
				php -r "echo str_replace( '<!-- EXTRA DOCS GO HERE! -->', \"$extra1\", file_get_contents( '/tmp/file3.html' ) ); " > $w
			done
			for w in ${DOC_OUTPUT_DIR}/${comp}_*.html ${DOC_OUTPUT_DIR}/*_${comp}.html; do
				echo "- Postprocessing $w"
				cp $w /tmp/file2.html
				php -r "echo preg_replace( '@/docs/api/(.*?)/@', '', file_get_contents( '/tmp/file2.html' ) ); " > /tmp/file3.html
				php -r "echo str_replace( '<!-- EXTRA DOCS GO HERE! -->', \"$extra2\", file_get_contents( '/tmp/file3.html' ) ); " > $w
			done
		fi

	else
		echo '<div class="attribute-heading"><h1>'$comp'</h1></div>' > ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo '<b>[ <a href="introduction_'$comp'.html" class="menu">Tutorial</a> ]</b>' >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo '<b>[ <a href="classtrees_'$comp'.html" class="menu">Class tree</a> ]</b>' >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo '<b>[ <a href="elementindex_'$comp'.html" class="menu">Element index</a> ]</b>' >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo '<b>[ <a href="changelog_'$comp'.html" class="menu">ChangeLog</a> ]</b>' >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo '<b>[ <a href="credits_'$comp'.html" class="menu">Credits</a> ]</b>' >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
		echo "<h1>No introduction available for $comp</h1>" >> ${DOC_OUTPUT_DIR}/introduction_$comp.html
	fi

	cat >> ${DOC_OUTPUT_DIR}/left_menu_comp.html << EOF
<li><a href="${HTTP_ROOT_DIR}/classtrees_$comp.html">$comp</a> ($version)</li>
EOF
done

cat >> ${DOC_OUTPUT_DIR}/left_menu_comp.html << EOF
</ul>
<hr/>

<ul>
<li><a href="${HTTP_ROOT_DIR}/allclassesindex.html">All Classes</a></li>
<li><a href="${HTTP_ROOT_DIR}/elementindex.html">All Elements</a></li>
</ul>

</div>
</div>
EOF

echo "* Tutorials overview page end"

cat >> ${DOC_OUTPUT_DIR}/tutorials.html << EOF
</ul>
EOF


cat > ${DOC_OUTPUT_DIR}/index.php << EOF
<?php
include 'overview.tpl';
?>
EOF

cd /tmp
wget -nH -m -p -np -k http://tequila/components/phpdoc_gen/ezcomponents-$release/
cd components/phpdoc_gen/ezcomponents-$release
ln -s /home/httpd/html/components/design .

for i in `egrep -rl "tequila/doc" *`; do perl -p -i -e 's/<link rel="Home" href="http:\/\/tequila\/doc" title="Doc front page" \/>//' $i; done
for i in `egrep -rl "tequila" *`; do perl -p -i -e "s/<a.*?href='http:\/\/tequila\/(.*?)>(.*?)<\/a>/\\2/g" $i; done
for i in `egrep -rl "tequila" *`; do perl -p -i -e 's/<a.*?href="http:\/\/tequila\/(.*?)>(.*?)<\/a>/\\2/g' $i; done

cd ..
tar -chzf /tmp/ezcomponents-$release-docs.tar.gz ezcomponents-$release
cd ../..
rm -rf components
scp -p /tmp/ezcomponents-$release-docs.tar.gz components.ez.no:/home/httpd/ezcomponents.org/files/downloads
echo "scp -p /tmp/ezcomponents-$release-docs.tar.gz components.ez.no:/home/httpd/ezcomponents.org/files/downloads"
