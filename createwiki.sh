#!/bin/bash
set -ex

mkdir $PATCHDEMO/$NAME
mkdir $PATCHDEMO/$NAME/w

# check out files
while IFS=' ' read -r repo dir; do
	git --git-dir=$PATCHDEMO/repositories/$repo/.git worktree add --detach $PATCHDEMO/$NAME/$dir $BRANCH
done < $PATCHDEMO/repositories.txt

# make database
mysql -u patchdemo --password=patchdemo -e "CREATE DATABASE patchdemo_$NAME";

# fetch dependencies
cd $PATCHDEMO/$NAME/w
composer update --no-dev

# install
php $PATCHDEMO/$NAME/w/maintenance/install.php \
--dbname=patchdemo_$NAME \
--dbuser=patchdemo \
--dbpass=patchdemo \
--confpath=$PATCHDEMO/$NAME/w \
--server="$SERVER" \
--scriptpath="/$NAME/w" \
--with-extensions \
--pass=patchdemo \
"$WIKINAME" "Patch Demo"
