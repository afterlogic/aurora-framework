#!/bin/bash

echo "Aurora Core";
git pull
echo "";

cd ../modules

for dir in $(find . -name ".git");
do
    cd ${dir%/*} > /dev/null;
    echo ${dir%/*};
    git pull;
	echo "";
    cd -  > /dev/null;
done

cd ../build

./build.sh

./docs/build-apidoc.sh