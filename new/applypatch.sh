#!/bin/bash
set -ex

cd $PATCHDEMO/wikis/$NAME/$REPO

# Required when updating an existing wiki
git reset --hard origin/master

git fetch origin $REF

# Apply $HASH and its parent commits up to $BASE on top of current HEAD.
#
# Consider the following situation: we've been asked to apply the patch 'rewrite-stuff' (with
# dependencies), which is based on 'master', onto the 'wmf/1.69.0-wmf.42' branch.
#
# (I hope you like ASCII-art inspired by Git's man pages)
#
#  ---A---B---C---D---E     master  ($BASE)
#      \           \
#       \           X---Y   rewrite-stuff  ($HASH)
#        \
#         P---Q             wmf/1.69.0-wmf.42  (HEAD)
#
# The trick in this command is to pick up commits X and Y, but not B through D, to achieve:
#
#  ---A---B---C---D---E     master
#      \           \
#       \           X---Y   rewrite-stuff
#        \
#         P---Q---X'--Y'    HEAD

git -c user.email="patchdemo@example.com" -c user.name="Patch Demo" rebase --onto HEAD $BASE $HASH
