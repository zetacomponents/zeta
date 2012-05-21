#!/bin/sh

for i in `grep -r "function" * | grep " array " | grep -v svn | grep -v '*' | grep php | sed "s/:.*//"`; do
	php scripts/fix-docs-array.php $i
done
