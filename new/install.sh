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
cat $PATCHDEMO/localsettings/core.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# apply extension/skin/service-sepcific setings
while IFS=' ' read -r repo dir; do
	filename=$(echo $repo | sed "s/\//-/g" | sed "s/^mediawiki-//")
	if [ -f $PATCHDEMO/localsettings/$filename.txt ]; then
		if [ -d $PATCHDEMO/wikis/$NAME/$dir ]; then
			echo -e "\n// $repo" >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
			cat $PATCHDEMO/localsettings/$filename.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
		fi
	fi
done < $PATCHDEMO/repository-lists/all.txt

# create htaccess
echo "RewriteEngine On
# main rewrite rule
RewriteRule ^/?wiki(/.*)?$ $PATCHDEMO/wikis/$NAME/w/index.php [L]
# Redirect / to Main Page
RewriteRule ^/*$ $PATCHDEMO/wikis/$NAME/w/index.php [L]" > $PATCHDEMO/wikis/$NAME/.htaccess
