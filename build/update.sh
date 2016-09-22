#!/bin/bash

#git pull

cd ../modules

for dir in $(find . -name ".git");
do
    cd ${dir%/*} > /dev/null;
    #echo ${dir%/*};
    #git pull;
    cd -  > /dev/null;
done

cd ../build

./build.sh