#!/bin/bash
set -e
echo "starting after_install.sh script"
wget -O /dev/null http://localhost/admin/index.php?route=marketplace/modification/refreshcron --header 'X-Forwarded-Proto: https' --header 'Host: www.chipoteka.hr'
echo "done with after_install.sh script"