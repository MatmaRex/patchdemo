#!/bin/bash
set -ex

git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree prune
# Canonicalize the path using `readlink -f` to avoid a bug where `git worktree`
# creates invalid refs when the path ends with '/.' (#446)
git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git worktree add \
	--detach $(readlink -f $PATCHDEMO/wikis/$NAME/$REPO_TARGET) $BRANCH
