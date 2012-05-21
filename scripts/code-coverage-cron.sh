#!/bin/sh

cd trunk
svn up >/dev/null 2>&1
/usr/local/bin/php UnitTest/src/runtests.php -c /home/httpd/html/components/codecoverage -D sqlite://:memory: >/dev/null 2>&1
