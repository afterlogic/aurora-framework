#!/bin/bash

git pull

cd ../modules

for dir in $(find . -name ".git");
do
    cd ${dir%/*} > /dev/null;
    echo ${dir%/*};
    git pull;
    cd -  > /dev/null;
done

cd .. > /dev/null;
gulp all;
./_build-styles.bat;
./_build-langs.bat;

