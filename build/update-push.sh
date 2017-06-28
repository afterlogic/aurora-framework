#!/bin/bash

update_push () 
{
	git add  -A
	git commit -m $3
	git pull

	loginWithPassword=$1":"$2"@github.com"
	loginWithAt=$1"@"
	emptyString=""
	guthubString="github.com"

	url="$(git config --get remote.origin.url)"
	url="${url/$loginWithAt/$emptyString}"
	resultUrl="${url/$guthubString/$loginWithPassword}"

	git push --repo $resultUrl
} 

read -p "GitHub Login: " login
read -p "GitHub Password: " password
read -p "Commit message: " commit

clear

echo "Aurora Core";

update_push $login $password "'"$commit"'"

echo "";

cd ../modules

for dir in $(find . -name ".git")
do
    cd ${dir%/*} > /dev/null
    
	echo ${dir%/*}

	update_push $login $password "'"$commit"'"

	echo "";

    cd -  > /dev/null
done