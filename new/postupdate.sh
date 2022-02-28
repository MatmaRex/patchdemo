#!/bin/bash
set -ex

# run update script (#166, #244)
php $PATCHDEMO/wikis/$NAME/w/maintenance/update.php --quick

# Update Main_Page
MAINPAGETITLE=$( echo 'echo Title::newMainPage()->getPrefixedText();' | php $PATCHDEMO/wikis/$NAME/w/maintenance/eval.php 2> /dev/null )
MAINPAGECURRENT=$( php $PATCHDEMO/wikis/$NAME/w/maintenance/getText.php "$MAINPAGETITLE" )
echo "$MAINPAGECURRENT$MAINPAGE" | php $PATCHDEMO/wikis/$NAME/w/maintenance/edit.php "$MAINPAGETITLE" || echo "Can't edit main page"
