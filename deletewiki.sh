#!/bin/bash
set -ex

rm -rf $PATCHDEMO/wikis/$WIKI

# delete database
mysql -u patchdemo --password=patchdemo -e "DROP DATABASE IF EXISTS patchdemo_$WIKI";
