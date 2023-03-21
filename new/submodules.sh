#!/bin/bash
set -ex

# Check for contents in the folder (#527)
if [ -f $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor/.git ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor
	git submodule update --init --recursive
fi

if [ -f $PATCHDEMO/wikis/$NAME/w/extensions/WikiLambda/.git ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/WikiLambda
	git submodule update --init --recursive
fi
