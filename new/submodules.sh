#!/bin/bash
set -ex

if [ -d $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor ]; then
	cd $PATCHDEMO/wikis/$NAME/w/extensions/VisualEditor
	git submodule update --init --recursive
fi
