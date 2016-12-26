#!/bin/bash

echo "Aurora Core";
git fetch --all;
git reset --hard origin/master;
echo "";

cd ../modules

for dir in $(find . -name ".git");
do
    cd ${dir%/*} > /dev/null;
    echo ${dir%/*};
    git fetch --all;
	git reset --hard origin/master;
	echo "";
    cd -  > /dev/null;
done

cd ../build

./build.sh

./docs/build-apidoc.sh