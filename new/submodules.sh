#!/bin/bash
set -ex

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor
	git submodule update --init --recursive
fi

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/WikiLambda ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/WikiLambda
	git submodule update --init --recursive
fi
