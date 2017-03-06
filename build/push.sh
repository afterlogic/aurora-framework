#!/bin/bash

read -p "Commit message: " response

echo "Aurora Core";
git add -A;
git commit -m $response;
git push origin/master;
echo "";

cd ../modules

for dir in $(find . -name ".git");
do
    cd ${dir%/*} > /dev/null;
    echo ${dir%/*};
    git add -A;
	git commit -m $response;
	git push origin/master;
    cd -  > /dev/null;
done