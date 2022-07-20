#!/bin/bash

# Allow passing a custom version string - defaults to "next"
SMOOTH_BUMP=false
VERSION=''

print_usage() {
  printf "./bump-version.sh -s [version_string]"
}

while getopts 'sv:' flag; do
  case "${flag}" in
    s) SMOOTH_BUMP=true ;;
    v) VERSION="${OPTARG}" ;;
    *) print_usage
       exit 1 ;;
  esac
done

LAST_PACKAGE_JSON=$(perl -ne 'print $1 while /\s*"version":\s\"(\d+\.\d+\.\d+)\"/sg' package.json)
LAST_PACKAGE=$(perl -ne 'print $1 while /\s*const\sVERSION\s=\s'\''(\d+\.\d+\.\d+)'\''/sg' src/Package.php)
LAST_MAIN_FILE=$(perl -ne 'print $1 while /\s*\*\sVersion:\s(\d+\.\d+\.\d+)/sg' woocommerce-germanized-shipments.php)

# Store the latest version detected in the actual files
LATEST=$(printf "$LAST_PACKAGE_JSON\n$LAST_PACKAGE\n$LAST_MAIN_FILE" | sort -V -r | head -1)

echo "Latest version detected in files: $LATEST"
NEXT_VERSION=$(echo ${LATEST} | awk -F. -v OFS=. '{$NF += 1 ; print}')

# Set the version to next version in case no version has been passed
if [ "$VERSION" == "" ]; then
    VERSION=$NEXT_VERSION
else
    TMP_LAST=$(printf "$LATEST\n$VERSION" | sort -V -r | head -1)
    # Do not bump the version in smooth mode in case the version has already been bumped.
    if [ "$SMOOTH_BUMP" == "true" ] && [ "$TMP_LAST" == "$LATEST" ]; then
        NEXT_VERSION=$LATEST
    fi
fi

# Use the latest version: Either detected in files or from custom argument
NEW_VERSION=$(printf "$NEXT_VERSION\n$VERSION" | sort -V -r | head -1)

echo "Version to be bumped: $NEW_VERSION"

export NEW_VERSION

perl -pe '/^\s*"version":/ and s/(\d+\.\d+\.\d+)/$2 . ("$ENV{'NEW_VERSION'}")/e' -i package.json
perl -pe '/^\s*const\sVERSION\s=\s/ and s/(\d+\.\d+\.\d+)/$2 . ("$ENV{'NEW_VERSION'}")/e' -i src/Package.php
perl -pe '/^\s*\*\sVersion:/ and s/(\d+\.\d+\.\d+)/$2 . ("$ENV{'NEW_VERSION'}")/e' -i woocommerce-germanized-shipments.php