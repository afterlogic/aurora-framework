#!/bin/bash

cd ../

zip -r aurora-cloud.zip data modules static system vendor ".htaccess" dav.php common.php index.php LICENSE favicon.ico robots.txt -x ./data/**\*

cd ./build