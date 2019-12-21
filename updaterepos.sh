#!/bin/bash
set -ex

while IFS=' ' read -r repo dir; do
	git --git-dir=$PATCHDEMO/repositories/$repo/.git fetch --all
done < $PATCHDEMO/repositories.txt
