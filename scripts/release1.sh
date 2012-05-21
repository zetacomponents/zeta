#!/bin/bash

BASEDIR=`pwd`

if test $# != 1; then
	echo "Usage: ./scripts/release1.sh [component]";
	exit;
fi

component=$1
echo

# figure out if we need to release from a branch or not
parts=`echo $component | cut -d / -s -f 2`;
if test "$parts" == ""; then
	branch='trunk';
	prefix='../..';
	unittestcmd='UnitTest/src/runtests.php';
	logfilename=$component
else
	branch='stable';
	prefix='../../..';
	unittestcmd='../trunk/UnitTest/src/runtests.php -r stable';
	logfilename=`echo $component | tr / -`;
fi

# figure out the DSNs to use
if test "$component" == "AuthenticationDatabaseTiein" \
	-o "$component" == "Database" \
	-o "$component" == "DatabaseSchema" \
	-o "$component" == "EventLogDatabaseTiein" \
	-o "$component" == "PersistentObject" \
	-o "$component" == "PersistentObjectDatabaseSchemaTiein" \
	-o "$component" == "TreePersistentObjectTiein" \
	-o "$component" == "TreeDatabaseTiein" \
	-o "$component" == "WorkflowDatabaseTiein"; then
	dsns="mysql://root:wee123@localhost/ezc sqlite://:memory: sqlite:///tmp/test.sqlite pgsql://ezc:ezc@localhost/ezc"
else
	dsns="sqlite:///tmp/test.sqlite:";
fi

cd $branch

echo "* Checking line endings"
cd $component
status=`$prefix/scripts/check-end-of-file-marker.sh`
if test "$status" != ""; then
	echo
	echo "Aborted: Line ending problems in:"
	echo $status
	exit
fi
cd - >/dev/null

echo "* Checking for local modifications"
status=`svn st --ignore-externals $component | grep -v 'X'`
if test "$status" != ""; then
	echo
	echo "Aborted: Local modifications:";
	echo $status
	exit
fi

echo "* Checking RST syntax in ChangeLog"
rst2html -q --exit-status=warning $component/ChangeLog > $BASEDIR/html/test.html
if test $? != 0; then
	echo
	echo "Aborted: RST Failed"
	exit
fi

echo "* Running tests"
for dsn in $dsns; do
	echo "  - $dsn"
	php $unittestcmd --verbose --dsn=$dsn $component |tee /tmp/test-$logfilename.log
	testresult=`cat /tmp/test-$logfilename.log | grep FAILURES`;
	if test "$testresult" == "FAILURES!"; then
		echo
		echo "Aborted: TESTS FAILED";
		exit
	fi
done

echo
echo "All clear"
