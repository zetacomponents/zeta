#!/bin/sh

for i in `find . -name \*.php`; do perl -p -i -e "s/^\s\*\s\@copyright(.*)/ * \@copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved./g" $i; done
