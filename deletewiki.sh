#!/bin/bash
set -ex

rm -rf wikis/$1

# delete database
mysql -u patchdemo --password=patchdemo -e "DROP DATABASE patchdemo_$1";
