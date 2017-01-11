#!/bin/bash

#Build static data
cd ../modules

Modules=();
for dir in *;
do
	if [ -d "$dir" ];then
		Modules=("${Modules[@]}" "$dir");
	fi
done

gulp styles --modules $(IFS=, ; echo "${Modules[*]}") --themes Default,Funny
