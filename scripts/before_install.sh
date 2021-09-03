#!/bin/bash
set -e
echo "starting search and replace on config files"
python3 getsecret.py
echo "done with search and replace on config files"

echo "starting before_install.sh script"
rsync -a --delete --exclude "upload/image/" ./  /home/chipoteka/public_html/
echo "done with before_install.sh script"
