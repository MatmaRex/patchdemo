#!/bin/bash
set -ex

git --git-dir=$PATCHDEMO/repositories/$REPO_SOURCE/.git fetch --all
