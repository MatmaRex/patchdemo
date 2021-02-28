#!/bin/bash
set -ex

# install
cd $PATCHDEMO/wikis/$NAME/w
php $PATCHDEMO/wikis/$NAME/w/maintenance/install.php \
--dbname=patchdemo_$NAME \
--dbuser=patchdemo \
--dbpass=patchdemo \
--confpath=$PATCHDEMO/wikis/$NAME/w \
--server="$SERVER" \
--scriptpath="$SERVERPATH/wikis/$NAME/w" \
--with-extensions \
--pass=patchdemo1 \
"$WIKINAME" "Patch Demo"

# apply our default settings
cat $PATCHDEMO/LocalSettings.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# create htaccess
echo "RewriteEngine On
# main rewrite rule
RewriteRule ^/?wiki(/.*)?$ $PATCHDEMO/wikis/$NAME/w/index.php [L]
# Redirect / to Main Page
RewriteRule ^/*$ $PATCHDEMO/wikis/$NAME/w/index.php [L]" > $PATCHDEMO/wikis/$NAME/.htaccess
