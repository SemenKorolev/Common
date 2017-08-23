#!/usr/bin/env bash

cd "`dirname "$0"`"
cd cgi

rm -rf vendor
[ -f composer.lock ] && mv composer.lock{,.old}
[ ! -f composer.phar ] && curl -sS 'https://getcomposer.org/installer' | php
php composer.phar install
php composer.phar update
patch vendor/monolog/monolog/src/Monolog/Formatter/NormalizerFormatter.php patch/normalizer.patch
patch vendor/monolog/monolog/src/Monolog/ErrorHandler.php patch/errorhandler.patch

which phpunit >/dev/null 2>&1
if [ "$?" -ne 0 ]; then
    echo "Installing phpunit..."
    wget "https://phar.phpunit.de/phpunit-5.7.phar" -O phpunit.phar
    chmod +x phpunit.phar
    mv phpunit.phar /usr/local/bin/phpunit
fi

if [ -d "../www/css" ]; then
    cd ../www/css
    [ -f sass.pid ] && kill `cat sass.pid`
    rm -f *.css *.css.map
    nohup sass --watch .:. --style compressed >sass.log 2>&1 &
    echo "$!" > sass.pid
    sleep 5
    cd -
fi

../js.min.sh
../css.min.sh

mkdir -p logs stat
mkdir -p ../www/screenshot
mkdir -p ../www/captcha
mkdir -p ../www/latex
mkdir -p ../www/upload/vc

./cron.sh
