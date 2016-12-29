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

chmod +x build.sh;
chmod +x update.sh;
chmod +x update-reset.sh;
chmod +x pack.sh;
chmod +x ./docs/build-apidoc.sh;

./build.sh

./docs/build-apidoc.sh