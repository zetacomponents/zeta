#!/bin/sh

if test $# -lt 1; then
	echo "Usage: scripts/build-docs.sh <version> ..."
	exit 0;
fi

release=$1

SOURCE_DIR="`pwd`"
BASE_OUTPUT_DIR="`pwd`/build/"
DOC_OUTPUT_DIR=${BASE_OUTPUT_DIR}/phpdoc_gen/ezcomponents-${release}

wd=`pwd`

rm -rf ${DOC_OUTPUT_DIR} || exit 6
rm -rf ${BASE_OUTPUT_DIR}/cdocs-${release}.tgz || exit 7

mkdir -p ${DOC_OUTPUT_DIR}
#ln -s /home/httpd/html/components/design ${DOC_OUTPUT_DIR}/design

echo "Copying overview"
cp docs/overview.tpl ${DOC_OUTPUT_DIR} || exit 12

mkdir -p ${DOC_OUTPUT_DIR}

echo "Writing config file for $release"
cd $wd
php scripts/build-php-doc-config.php ${SOURCE_DIR} ${DOC_OUTPUT_DIR} $release on > /tmp/doc-components.ini || exit 1

j=`php scripts/list-export-dirs.php $release`

cd ${SOURCE_DIR} || exit 2

mkdir -p ${DOC_OUTPUT_DIR} || exit 8

# @todo: Such files do not exist - what is this supposed to do?
#echo "Copying overview for $release"
#cp docs/overview_$release.tpl ${DOC_OUTPUT_DIR}
# Trying to fix that:
cp docs/overview.tpl "${DOC_OUTPUT_DIR}/overview_${release}.tpl"

echo "Running php documentor for $release"
phpdoc -q on -c /tmp/doc-components.ini >/tmp/docbuild-$release.log 2>&1 || exit 8
./scripts/setup-env.sh

echo "Writing left_menu_comp_$release.tpl"

cat > ${DOC_OUTPUT_DIR}/left_menu_comp_$release.html << EOF
<div class="attribute-heading">
<h1>eZ Components $release</h1>
</div>
<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="/docs/install">Installation</a></li>
<li><a href="/docs/api/$release/tutorials.html">Tutorials</a></li>
</ul>

<h2>Components</h2>
<ul>
EOF


cat > ${DOC_OUTPUT_DIR}/index.php << EOF
<?php
include 'overview_$release.tpl';
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

# Add changelog and CREDITS
	php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $i/ChangeLog -o "changelog_$comp.html"
	php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $i/CREDITS -o "credits_$comp.html"

# Process tutorial
	if test -f $i/docs/tutorial.txt; then
		echo "* $comp ($version)"
		php scripts/render-tutorial.php -c $comp -t ${DOC_OUTPUT_DIR} -v $version -r $release

		cat >> ${DOC_OUTPUT_DIR}/tutorials.tpl << EOF
<li><a href="introduction_$comp.html')}">$comp</a></li>
EOF
		cat >> ${DOC_OUTPUT_DIR}/tutorials.html << EOF
<li><a href="introduction_$comp.html">$comp</a></li>
EOF

# Add extra docs for tutorials
		extra1=""
		extra2=""
		for t in $i/docs/*.txt; do
			branch=`echo $t | cut -d / -f 1`;
			if test "$branch" = "trunk"; then
				output_name=`echo $t | cut -d / -f 4 | sed 's/.txt/.html/'`;
			else
				output_name=`echo $t | cut -d / -f 5 | sed 's/.txt/.html/'`;
			fi
			if test $output_name != "tutorial.html"; then
				if test $output_name != "docs"; then
					echo -n "  - Rendering extra doc '$output_name' to $release/${comp}_${output_name}"
					php scripts/render-rst-file.php -v $release -c $comp -t "${DOC_OUTPUT_DIR}" -f $t
					short_name=`echo $output_name | sed 's/.html//'`
					short_name=`php -r "echo strtr( ucfirst( '$short_name' ), '-_', '  ' );"`
					extra1="$extra1 <b>[ <a href='../${comp}_${output_name}'>$short_name</a> ]</b>"
					extra2="$extra2 <b>[ <a href='${comp}_${output_name}'>$short_name</a> ]</b>"
				fi
			fi
		done
		if test "$extra1" != ""; then
			for w in ${DOC_OUTPUT_DIR}/$comp/*.html; do
				echo "- Postprocessing $w"
				cp $w /tmp/file.html
				php -r "echo str_replace( '<!-- EXTRA DOCS GO HERE! -->', \"$extra1\", file_get_contents( '/tmp/file.html' ) ); " > $w
			done
			for w in ${DOC_OUTPUT_DIR}/${comp}_*.html ${DOC_OUTPUT_DIR}/*_${comp}.html; do
				echo "- Postprocessing $w"
				cp $w /tmp/file.html
				php -r "echo str_replace( '<!-- EXTRA DOCS GO HERE! -->', \"$extra2\", file_get_contents( '/tmp/file.html' ) ); " > $w
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

	cat >> ${DOC_OUTPUT_DIR}/left_menu_comp_$release.tpl << EOF
<li><a href="{concat(\$indexDir, '/components/view/$release/(file)/classtrees_$comp.html')}">$comp</a> ($version)</li>
EOF
	cat >> ${DOC_OUTPUT_DIR}/left_menu_comp_$release.html << EOF
<li><a href="/docs/api/$release/classtrees_$comp.html">$comp</a> ($version)</li>
EOF
done

cat >> ${DOC_OUTPUT_DIR}/left_menu_comp_$release.html << EOF
</ul>
<hr/>

<ul>
<li><a href="/docs/api/$release/allclassesindex.html">All Classes</a></li>
<li><a href="/docs/api/$release/elementindex.html">All Elements</a></li>
</ul>

</div>
</div>
EOF

echo "* Tutorials overview page end"

cat >> ${DOC_OUTPUT_DIR}/tutorials.tpl << EOF
</ul>
EOF
cat >> ${DOC_OUTPUT_DIR}/tutorials.html << EOF
</ul>
EOF

cat > ${DOC_OUTPUT_DIR}/index.php << EOF
<?php
include 'overview.tpl';
?>
EOF

cd ${BASE_OUTPUT_DIR} || exit 10

for i in `find . | grep %%`; do
	rm $i
done

cd phpdoc_gen
tar -cf ../cdocs-${release}.tar ezcomponents-${release} || exit 11
cd ..
gzip -c -9 cdocs-${release}.tar > cdocs-${release}.tgz || exit 12
rm cdocs-${release}.tar

echo
echo
echo "Now execute:"
echo "scp -p ${BASE_OUTPUT_DIR}cdocs-${release}.tgz components.ez.no:"
echo "ssh components.ez.no ./copy-doc.sh cdocs-${release}.tgz"

