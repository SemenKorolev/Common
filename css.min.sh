#!/usr/bin/env bash

cd "`dirname "$0"`"
cd cgi
cd ../www/css
cat style.css bbcode.css mce.css form.css >all.css
cat ../chessboard/css/chessboard-0.3.0.css >>all.css
