#!/bin/bash
set -ex

cd $PATCHDEMO/$NAME/$REPO

git fetch origin $REF
git -c user.email="patchdemo@example.com" -c user.name="Patch Demo" cherry-pick -x $HASH
