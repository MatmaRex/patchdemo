#!/bin/bash
set -ex

mkdir $PATCHDEMO/wikis/$NAME
mkdir $PATCHDEMO/wikis/$NAME/w

# check out files
while IFS=' ' read -r repo dir; do
	git --git-dir=$PATCHDEMO/repositories/$repo/.git worktree prune
	git --git-dir=$PATCHDEMO/repositories/$repo/.git worktree add --detach $PATCHDEMO/wikis/$NAME/$dir $BRANCH
done <<< "$REPOSITORIES"

# make database
mysql -u patchdemo --password=patchdemo -e "CREATE DATABASE patchdemo_$NAME";

# fetch dependencies
cd $PATCHDEMO/wikis/$NAME/w
composer update --no-dev

if [ -d $PATCHDEMO/wikis/$NAME/w/parsoid ]; then
	cd $PATCHDEMO/wikis/$NAME/w/parsoid
	composer update --no-dev
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor
	git submodule update --init --recursive
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/TemplateStyles ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/TemplateStyles
	composer update --no-dev
fi

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

if [ -n "${CREATOR}" ]
then
	echo $CREATOR > $PATCHDEMO/wikis/$NAME/creator.txt
fi
date +%s > $PATCHDEMO/wikis/$NAME/created.txt

# apply our default settings
cat $PATCHDEMO/LocalSettings.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# update Main_Page
sleep 1 # Ensure edit appears after creation in history
echo "$MAINPAGE" | php $PATCHDEMO/wikis/$NAME/w/maintenance/edit.php "Main_Page"

# run update script (#166)
php $PATCHDEMO/wikis/$NAME/w/maintenance/update.php --quick

# grant FlaggedRevs editor rights to the default account
if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/FlaggedRevs ]; then
	php $PATCHDEMO/wikis/$NAME/w/maintenance/createAndPromote.php "Patch Demo" --force --custom-groups editor
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

