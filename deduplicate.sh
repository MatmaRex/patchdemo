#!/bin/bash

BASEDIR=$(dirname "$0")

# Using hardlinks assumes that these files will never change,
# as changing any of the deduplicated copies would affect all of them.

# We can't use symlinks because PHP files using __FILE__, __DIR__ etc.
# resolve them to the target file.

# We could use reflinks but they only work on some filesystems, so
# I'd have to figure out how to provision instances using XFS or something.
# (Also, `rdfind` doesn't have an option for that, `rmlint` could be used.)

# Only loook inside the "w" folder as while we are updating a wiki it is
# called "w-updating" and shouldn't be deduplicated.
find $BASEDIR/wikis -name "w" -type d \
	xargs sudo -u www-data rdfind -makehardlinks true -makeresultsfile false -checksum sha1
