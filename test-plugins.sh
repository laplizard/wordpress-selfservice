#!/bin/sh

find plugins \( -iname '*.php' \) -print0 | xargs -n1 -0 php -l &&
find tests \( -iname '*.php' \) -print0 | xargs -n1 -0 php -l &&
phpcs -psvn --standard=WordPress src &&
phpunit -c phpunit.xml

