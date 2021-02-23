#!/bin/bash
set -ex

# update Main_Page
sleep 1 # Ensure edit appears after creation in history
echo "$MAINPAGE" | php $PATCHDEMO/wikis/$NAME/w/maintenance/edit.php "Main_Page"

# run update script (#166, #244)
php $PATCHDEMO/wikis/$NAME/w/maintenance/update.php --quick

# grant FlaggedRevs editor rights to the default account
if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/FlaggedRevs ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups editor
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/SecurePoll ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups electionadmin
fi

# import XML dumps
for page in $(find $PATCHDEMO/pages -name "*.xml" -not -type d -printf '%P\n')
do
	php $PATCHDEMO/wikis/$NAME/w/maintenance/importDump.php < $PATCHDEMO/pages/$page
done

# update caches after import
php $PATCHDEMO/wikis/$NAME/w/maintenance/rebuildrecentchanges.php
php $PATCHDEMO/wikis/$NAME/w/maintenance/initSiteStats.php

# copy logo
cp $PATCHDEMO/images/logo.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/wordmark.svg $PATCHDEMO/wikis/$NAME/w/
cp $PATCHDEMO/images/favicon.ico $PATCHDEMO/wikis/$NAME/w/
