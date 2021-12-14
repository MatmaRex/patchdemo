#!/bin/bash
set -ex

rm -rf $PATCHDEMO/wikis/$WIKI

rm -f $PATCHDEMO/logs/$WIKI.html

# delete database
mysql -u patchdemo --password=patchdemo -e "DROP DATABASE IF EXISTS patchdemo_$WIKI";
