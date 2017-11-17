#!/bin/bash
set -e

# Add write-key
eval `ssh-agent -s`
ssh-add ~/.ssh/write-key
echo "latest git commit: "
git log -1 --pretty=%B
# Check
if [ $1 == "pipelines-build-master" ] && git log -1 --pretty=%B | grep -q -E "(feat\(|fix\(|refactor\()"
then
 git --version
 #workaround: do a full clone and merge master into staging
 cd $SOURCE_DIR/../
 mkdir source_copy
 git clone git@github.com:acquia/pipelines-ui.git source_copy
 cd source_copy
 git config user.email "pipelines-cd@no-emails.com"
 git config user.name "Pipelines CD"
 git fetch --all
 git checkout master
 node $SOURCE_DIR/scripts/jira-create-version-pipelines-ui.js
 git describe --exact-match --abbrev=0 | xargs git tag -d
 npm run changelog
 git push --follow-tags origin master
 git checkout staging
 echo "merging changes from master into staging overwriting any conflicts with master"
 git merge origin/master -X theirs -m "Staging Release"
 echo "pushing to staging"
 git push origin staging
elif [ $1 == "pipelines-build-staging" ]
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
 node $SOURCE_DIR/scripts/jira-release-versions-pipelines-ui.js
 node $SOURCE_DIR/scripts/bugsnag-versions-pipelines-ui.js
# node $SOURCE_DIR/scripts/jira-create-doc-ticket.js
fi
