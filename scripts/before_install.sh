#!/bin/bash
set -e
# position to project dir
SCRIPT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $SCRIPT_PATH/../

# get the project root
PROJECT_ROOT=$(pwd)

echo "starting search and replace on config files"
python3 $PROJECT_ROOT/scripts/getsecret.py $PROJECT_ROOT
echo "done with search and replace on config files"

echo "starting before_install.sh script"
rsync -a --delete --exclude "$PROJECT_ROOT/upload/image/" --exclude "$PROJECT_ROOT/scripts/" "$PROJECT_ROOT/"  /home/chipoteka/public_html/
echo "done with before_install.sh script"
