#!/bin/sh

for i in `find . -name \*.php`; do perl -p -i -e "s/^\s\*\s\@license(.*)/ * \@license http:\/\/ez.no\/licenses\/new_bsd New BSD License/g" $i; done
