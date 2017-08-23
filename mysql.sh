#!/usr/bin/env bash

cd "`dirname "$0"`"
dump=""

if [ "$1" == "--dump" ]; then
    dump="dump"
    shift
fi

function config() {
    php cgi/bootstrap.php config "$1"
}

host=`config sql.host`
user=`config sql.user`
pass=`config sql.pass`
port=`config sql.port`
db=`config sql.db`
mysql"$dump" --protocol=tcp -h "$host" -P "$port" -u "$user" --password="$pass" "$db" "$@"
