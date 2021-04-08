#!/bin/sh

# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
	echo "$(tput setaf "$1")$2$(tput sgr0)"
}

# Autoloader
output 3 "Updating autoloader classmaps..."
composer dump-autoload
output 2 "Done"

if [ ! -d "vendor/dvdoug/boxpacker" ]; then
	output 1 "./vendor/dvdoug/boxpacker doesn't exist!"
	output 1 "run \"composer install\" before proceed."
fi

output 3 "Clean vendor dirs to save space..."

rm -rf ./vendor/dvdoug/boxpacker/visualiser/*
rm -rf ./vendor/dvdoug/boxpacker/tests/*

output 2 "Done!"