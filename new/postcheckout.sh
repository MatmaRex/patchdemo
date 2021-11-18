#!/bin/bash
set -ex

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

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/AbuseFilter ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/AbuseFilter
	composer update --no-dev
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/TemplateStyles ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/TemplateStyles
	composer update --no-dev
fi
