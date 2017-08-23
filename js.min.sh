#!/usr/bin/env bash

cd "`dirname "$0"`"
cd cgi
tmp=`mktemp`
nl=`mktemp`
cat ../www/js/script.js | php bootstrap.php minify_js >"$tmp"
cd ../www/js
echo >"$nl"
cat jquery-3.2.1.min.js "$nl" jquery.form.min.js "$nl" "$tmp" "$nl" >all.js
rm -f "$tmp" "$nl"
