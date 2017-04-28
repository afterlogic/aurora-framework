#!/bin/bash

cd ../

now=$(date +"%d-%m-%Y")

echo $now > VERSION

#empty data folder
#zip -r aurora-cloud.zip data modules static system vendor ".htaccess" dav.php common.php index.php LICENSE favicon.ico robots.txt -x ./data/**\*


#data/settings only
zip -r aurora-cloud.zip data/settings/modules modules static system vendor  ".htaccess" dav.php common.php index.php LICENSE VERSION favicon.ico robots.txt -x **/*.bak


cd ./build