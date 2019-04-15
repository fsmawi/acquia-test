#!/usr/bin/env bash

set -e -x
if [ -x /usr/local/bin/gsed ]; then
    gsed -i '/vendor/d' ./.gitignore
    gsed -i "s/return \$this->sitegroup/return 'enterprise-g1:sfwiptravis'/g" ./src/Acquia/Wip/AcquiaCloud/CloudCredentials.php
else
    sed -i '/vendor/d' ./.gitignore
    sed -i "s/return \$this->sitegroup/return 'enterprise-g1:sfwiptravis'/g" ./src/Acquia/Wip/AcquiaCloud/CloudCredentials.php
fi

git rm -r vendor || true
rm -rf ./vendor
git add ./.gitignore
git commit -m "removing vendor from gitignore"

composer install
find ./vendor -name .git | xargs rm -rf
git add ./vendor
git commit -m "adding in the vendor dir"

git add ./src/Acquia/Wip/AcquiaCloud/CloudCredentials.php
git commit --allow-empty -m "added the CloudCredentials hack" ./src/Acquia/Wip/AcquiaCloud/CloudCredentials.php

set +x
echo ""
echo "Deployment built, push it to your dev. stack repo."
echo "(Assuming dev-stack is your stage's remote like matyaswip@svn-2.sprint141.srvs.ahdev.co:matyaswip.git)"
echo "git push -u dev-stack <branch>-yourname"
