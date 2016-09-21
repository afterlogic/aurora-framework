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


#Build static data

Modules=();
for dir in *;
do
	if [ -d "$dir" ];then
		Modules=("${Modules[@]}" "$dir");
	fi
done

cd .. > /dev/null;
# gulp all;
# ./_build-styles.bat;
node ./node_modules/gulp/bin/gulp.js styles --modules $(IFS=, ; echo "${Modules[*]}") --themes Default,Funny
# ./_build-langs.bat;
node ./node_modules/gulp/bin/gulp.js langs --modules $(IFS=, ; echo "${Modules[*]}") --langs Arabic,Bulgarian,Chinese-Simplified,Chinese-Traditional,Czech,Danish,Dutch,English,Estonian,Finnish,French,German,Greek,Hebrew,Hungarian,Italian,Japanese,Korean,Latvian,Lithuanian,Norwegian,Persian,Polish,Portuguese-Brazil,Portuguese-Portuguese,Romanian,Russian,Serbian,Slovenian,Spanish,Swedish,Thai,Turkish,Ukrainian,Vietnamese
# ./_build-js.bat;
node ./node_modules/gulp/bin/gulp.js js1:build --output app --modules $(IFS=, ; echo "${Modules[*]}")

