#!/bin/bash
set -ex

git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree prune

# Make sure the path we're passing in exists, otherwise `readlink -f` will fail
mkdir -p $PATCHDEMO/wikis/$NAME/$REPO_TARGET

# Canonicalize the path using `readlink -f` to avoid a bug where `git worktree`
# creates invalid refs when the path ends with '/.' (#446)
git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree add \
	--detach $(readlink -f $PATCHDEMO/wikis/$NAME/$REPO_TARGET) $BRANCH
