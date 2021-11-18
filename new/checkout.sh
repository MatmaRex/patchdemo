#!/bin/bash
set -ex

git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree prune
git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree add --detach $PATCHDEMO/wikis/$NAME/$REPO_TARGET $BRANCH
