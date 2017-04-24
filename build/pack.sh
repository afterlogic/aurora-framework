#!/bin/bash

cd ../

#empty data folder
#zip -r aurora-cloud.zip data modules static system vendor ".htaccess" dav.php common.php index.php LICENSE favicon.ico robots.txt -x ./data/**\*

#data/settings only
zip -r aurora-cloud.zip data/settings/modules modules static system vendor ".htaccess" dav.php common.php index.php LICENSE favicon.ico robots.txt

cd ./build