#!/bin/bash
set -ex

cd $PATCHDEMO/wikis/$NAME/$REPO_TARGET
composer update --no-dev
