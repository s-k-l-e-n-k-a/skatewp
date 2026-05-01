#!/bin/bash

if ! version=$(grep -oE '\* Version:[[:space:]]+[0-9]+\.[0-9]+\.[0-9]+' ./style.css | grep -oE '[0-9]+\.[0-9]+\.[0-9]+'); then
  >&2 echo "could not get verion number from style.css"
  exit 1
fi

versionSlug=$(echo $version | sed "s/\./-/g")

mkdir -p temp_dir/pace4
cp -r * temp_dir/pace4/ 2>/dev/null
cd temp_dir
zip -rqq $versionSlug.zip ./ -x ./pace4/ci/\* ./pace4/.gitignore ./pace4/.gitlab-ci.yml ./pace4/.idea/\* ./pace4/.git/\* ./pace4/temp_dir/\*

if ! output=$(aws s3api put-object --bucket wordliner-wlac --key pace4/releases/$versionSlug.zip --body ./$versionSlug.zip 2>&1); then
  >&2 echo "failed to upload zip to s3"
  >&2 echo $output
  exit 1
fi

cat << EOF > ./update-metadata.json
{
  "version": "$version",
  "download_url": "https://wordliner-wlac.s3.eu-central-1.amazonaws.com/pace4/releases/$versionSlug.zip"
}
EOF

if ! output=$(aws s3api put-object --bucket wordliner-wlac --key pace4/update-metadata.json --content-type "application/json" --content-disposition inline --body ./update-metadata.json 2>&1); then
  >&2 echo "failed to update metadata for release"
  >&2 echo $output
  exit 1
fi