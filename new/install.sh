#!/bin/bash
set -ex

# make database
mysql -u patchdemo --password=patchdemo -e "CREATE DATABASE patchdemo_$NAME";

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
echo "\$wgLanguageCode = '$LANGUAGE';" >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
cat $PATCHDEMO/localsettings/core.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# apply extension/skin/service-specific settings
while IFS=' ' read -r repo dir; do
	filename=$(echo $repo | sed "s/\//-/g" | sed "s/^mediawiki-//")
	if [ -f $PATCHDEMO/localsettings/$filename.txt ]; then
		echo -e "\n// $repo" >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
		cat $PATCHDEMO/localsettings/$filename.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
	fi
done <<< "$REPOSITORIES"

# create htaccess
echo "RewriteEngine On
# main rewrite rule
RewriteRule ^/?wiki(/.*)?$ $PATCHDEMO/wikis/$NAME/w/index.php [L]
# Redirect / to Main Page
RewriteRule ^/*$ $PATCHDEMO/wikis/$NAME/w/index.php [L]" > $PATCHDEMO/wikis/$NAME/.htaccess
