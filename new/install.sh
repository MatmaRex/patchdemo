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

echo "\$wgLanguageCode = '$LANGUAGE';" >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

mkdir $PATCHDEMO/wikis/$NAME/w/settings.d
echo 'foreach( glob( __DIR__ . "/settings.d/*.php" ) as $conffile ) { include_once $conffile; }' >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# apply core/extension/skin/service-specific settings
while IFS=' ' read -r repo dir; do
	filename=$(echo $repo | sed "s/\//-/g" | sed "s/^mediawiki-//")
	if [ -f $PATCHDEMO/localsettings/$filename.php ]; then
		cp $PATCHDEMO/localsettings/$filename.php $PATCHDEMO/wikis/$NAME/w/settings.d
	fi
done <<< "$REPOSITORIES"

# create htaccess
echo "RewriteEngine On
# main rewrite rule
RewriteRule ^/?wiki(/.*)?$ $PATCHDEMO/wikis/$NAME/w/index.php [L]
# Redirect / to Main Page
RewriteRule ^/*$ $PATCHDEMO/wikis/$NAME/w/index.php [L]" > $PATCHDEMO/wikis/$NAME/.htaccess
