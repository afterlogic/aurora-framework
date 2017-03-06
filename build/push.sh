#!/bin/bash

read -p "Commit message: " commit
read -p "GitHub Login: " login
read -p "GitHub Password: " password

echo "Aurora Core";
git add -A
git commit -m $commit;

url = "${git config --get remote.origin.url}"

echo $url

#git push origin/master
#echo "";

#cd ../modules

#for dir in $(find . -name ".git")
#do
#    cd ${dir%/*} > /dev/null
#    echo ${dir%/*}
#    git add -A
#	git commit -m $response
#	git push origin/master
#    cd -  > /dev/null
#done