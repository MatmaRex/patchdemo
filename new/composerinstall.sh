#!/bin/bash
set -ex

if [ -f $PATCHDEMO/wikis/$NAME/$REPO_TARGET/composer.json ]; then
	cd $PATCHDEMO/wikis/$NAME/$REPO_TARGET
	composer install --no-dev
fi
