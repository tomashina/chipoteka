#!/bin/bash
set -e
echo "starting after_install.sh script"
wget -O /dev/null https://testing.chipoteka.hr/admin/index.php?route=marketplace/modification/refreshcron
echo "done with after_install.sh script"