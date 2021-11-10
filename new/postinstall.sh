#!/bin/bash
set -ex

# update Main_Page
sleep 1 # Ensure edit appears after creation in history
echo "$MAINPAGE" | php $PATCHDEMO/wikis/$NAME/w/maintenance/edit.php "Main_Page"

# run update script (#166, #244)
php $PATCHDEMO/wikis/$NAME/w/maintenance/update.php --quick

# create additional accounts
# generic accounts alice/bob e.g. for messaging tests
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Alice patchdemo1
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Bob patchdemo1
# blocked account
php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php Mallory patchdemo1
# This command may fail as --disable-autoblock was only added in 1.36, so suppress errors
echo "Mallory" | php $PATCHDEMO/wikis/$NAME/w/maintenance/blockUsers.php --reason "Blocking account for testing" --disable-autoblock || echo "Can't block Mallory"

# set dummy email addresses, in case Inbox is being used (#254)
php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Patch Demo" Patch_Demo@localhost
php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Alice" Alice@localhost
php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Bob" Bob@localhost
php $PATCHDEMO/wikis/$NAME/w/maintenance/resetUserEmail.php --no-reset-password "Mallory" Mallory@localhost

# run arbitrary SQL
for sql in $(find $PATCHDEMO/sql-perwiki -name "*.sql" -not -type d -printf '%P\n')
do
	mysql -u patchdemo -ppatchdemo patchdemo_$NAME < $PATCHDEMO/sql-perwiki/$sql
done

# grant FlaggedRevs editor rights to the default account
if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/FlaggedRevs ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups editor
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/SecurePoll ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups electionadmin
fi

# import extension/skin/service-specific XML dumps
while IFS=' ' read -r repo dir; do
	filename=$(echo $repo | sed "s/\//-/g" | sed "s/^mediawiki-//")
	if [ -d $PATCHDEMO/wikis/$NAME/$dir ]; then
		# matches extension-foo.xml or extension-foo-*.xml
		for page in $(find $PATCHDEMO/pages -regextype egrep -regex ".*/$filename(-.+)?.xml" -not -type d -printf '%P\n')
		do
			echo "Importing $PATCHDEMO/pages/$page"
			php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages/$page
		done
	fi
done < $PATCHDEMO/repository-lists/all.txt

# import generic XML dumps (core-*.xml)
for page in $(find $PATCHDEMO/pages -name "core-*.xml" -not -type d -printf '%P\n')
do
	echo "Importing $PATCHDEMO/pages/$page"
	php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages/$page
done

# Add the proxy if selected
if [ "${USE_PROXY}" = "1" ]; then
	cat $PATCHDEMO/localsettings/feature-proxy.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
	# Import custom Common.js for fetching CSS from the wiki
	for page in $(find $PATCHDEMO/pages-proxy -name "*.xml" -not -type d -printf '%P\n')
	do
		php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages-proxy/$page
	done
fi

# Enable instantCommons if selected
if [ "${USE_INSTANT_COMMONS}" = "1" ]; then
	cat $PATCHDEMO/localsettings/feature-instantCommons.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php
fi

# populate interwiki table from en.wiki
php $PATCHDEMO/wikis/$NAME/w/maintenance/populateInterwiki.php

# update caches after import
php $PATCHDEMO/wikis/$NAME/w/maintenance/rebuildrecentchanges.php
php $PATCHDEMO/wikis/$NAME/w/maintenance/initSiteStats.php

# copy logo
cp $PATCHDEMO/images/logo.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/icon.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/wordmark.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/favicon.ico $PATCHDEMO/wikis/$NAME/w/
