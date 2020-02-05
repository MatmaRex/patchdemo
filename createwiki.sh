#!/bin/bash
set -ex

mkdir $PATCHDEMO/wikis/$NAME
mkdir $PATCHDEMO/wikis/$NAME/w

# check out files
while IFS=' ' read -r repo dir; do
	git --git-dir=$PATCHDEMO/repositories/$repo/.git worktree prune
	git --git-dir=$PATCHDEMO/repositories/$repo/.git worktree add --detach $PATCHDEMO/wikis/$NAME/$dir $BRANCH
done < $PATCHDEMO/repositories.txt

# make database
mysql -u patchdemo --password=patchdemo -e "CREATE DATABASE patchdemo_$NAME";

# fetch dependencies
cd $PATCHDEMO/wikis/$NAME/w
composer update --no-dev

cd $PATCHDEMO/wikis/$NAME/w/parsoid
composer update --no-dev

cd $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor
git submodule update --init --recursive

# install
cd $PATCHDEMO/wikis/$NAME/w
php $PATCHDEMO/wikis/$NAME/w/maintenance/install.php \
--dbname=patchdemo_$NAME \
--dbuser=patchdemo \
--dbpass=patchdemo \
--confpath=$PATCHDEMO/wikis/$NAME/w \
--server="$SERVER" \
--scriptpath="/wikis/$NAME/w" \
--with-extensions \
--pass=patchdemo \
"$WIKINAME" "Patch Demo"

# apply our default settings
cat $PATCHDEMO/LocalSettings.txt >> $PATCHDEMO/wikis/$NAME/w/LocalSettings.php

# copy logo
cp $PATCHDEMO/logo.svg $PATCHDEMO/wikis/$NAME/w/

