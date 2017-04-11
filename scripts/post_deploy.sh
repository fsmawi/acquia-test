#!/bin/bash
set -e

# Add write-key
eval `ssh-agent -s`
ssh-add ~/.ssh/write-key
echo "latest git commit: "
git log -1 --pretty=%B
# Check
if [ $1 == "master" ] && git log -1 --pretty=%B | grep -q -E "(feat\(|fix\(|refactor\()"
then
 git --version
 git config core.sshCommand "ssh -i ~/.ssh/write-key -F /dev/null"
 git config user.email "pipelines-cd@no-emails.com" 
 git config user.name "Pipelines CD"
 git reset --hard HEAD
 git checkout $1
 npm version patch
 git push origin $1
 #workaround: do a full clone and merge master into staging
 cd $SOURCE_DIR/../
 mkdir source_copy
 git clone git@github.com:acquia/pipelines-ui.git source_copy
 cd source_copy
 git fetch --all
 git checkout staging
 echo "merging changes from master into staging overwriting any conflicts with master"
 git config user.email "pipelines-cd@no-emails.com"
 git config user.name "Pipelines CD"
 git merge origin/master -X theirs -m "Staging Release"
 echo "pushing to staging"
 git push origin staging
elif [ $1 == "staging" ]
then
 #workaround: do a full clone and merge master into staging
 cd $SOURCE_DIR/../
 mkdir source_copy
 git clone git@github.com:acquia/pipelines-ui.git source_copy
 cd source_copy
 git fetch --all
 git checkout production
 echo "merging changes from staging into production overwriting any conflicts with staging"
 git config user.email "pipelines-cd@no-emails.com"
 git config user.name "Pipelines CD"
 git merge origin/staging -X theirs -m "Production release"
 git push origin production
fi
