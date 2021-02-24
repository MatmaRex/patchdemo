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
