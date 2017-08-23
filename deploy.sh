#!/usr/bin/env bash

#!/usr/bin/env bash

this=`realpath "$0"`
this_dir=`dirname "$this"`
cd "$this_dir"
prefix=`basename "$this_dir"`

[ "$EUID" -ne "0" ] && { echo "RUN AS ROOT!"; exit 1; }

vars=()
vars+=("USE_HOST ${prefix}.dev")
vars+=("DOCKER_NAME_PREFIX ${prefix}_")
vars+=('DOCKER_IMAGE_PREFIX ejzspb/')
vars+=('ELASTICSEARCH_HOST ')
vars+=('LATEX_HOST ')
vars+=('SELENIUM_HOST ')
vars+=('MARIADB_HOST ')
vars+=('MARIADB_USER user')
vars+=('MARIADB_PASS pass')
vars+=("MARIADB_DBNAME ${prefix}")

for var in "${vars[@]}"; do
    one=`echo "$var" | cut -d" " -f1`
    two=`echo "$var" | cut -d" " -f2-`
    temp="${!one}"
    if [ -z "$temp" ] && [ -f "vars/${one}" ]; then
        temp=`cat "vars/${one}"`
    fi
    [ "$temp" ] && two="$temp"
    echo -n "Set ${one} (defaults to ${two}): "
    read input
    if [ -z "$input" ]; then
        temp="$two"
    else
        temp="$input"
    fi
    mkdir -p vars
    echo "$temp" > "vars/${one}"
    eval export "$one"='$temp'
done

rm -f cgi/local.ini
list=`docker ps -a --filter "name=^/${DOCKER_NAME_PREFIX}" | awk '{print $1}' | tail -n +2`
if [ "$list" ]; then
    echo "Delete Docker containers with prefix ${DOCKER_NAME_PREFIX}:"
    docker rm -f -v $list
fi

if [ -z "$MARIADB_HOST" ]; then
    docker pull "$DOCKER_IMAGE_PREFIX"mariadb
    docker create -v /var/lib/mysql --name "$DOCKER_NAME_PREFIX"mariadb_data ubuntu
    docker run -d --volumes-from "$DOCKER_NAME_PREFIX"mariadb_data --name "$DOCKER_NAME_PREFIX"mariadb \
        -e "MYSQL_RANDOM_ROOT_PASSWORD=yes" -e "MYSQL_DATABASE=${MARIADB_DBNAME}" \
        -e "MYSQL_USER=${MARIADB_USER}" -e "MYSQL_PASSWORD=${MARIADB_PASS}" "$DOCKER_IMAGE_PREFIX"mariadb
    { sleep 2; docker ps | grep -q "$DOCKER_NAME_PREFIX"mariadb; } || { echo "MariaDB failed to run!"; exit 1; }
    ip=`docker inspect "$DOCKER_NAME_PREFIX"mariadb | grep IPAddress | tail -1 | cut -d'"' -f4`
    echo "MariaDB IP: ${ip}"
    MARIADB_HOST="$ip"
fi

if [ -z "$ELASTICSEARCH_HOST" ]; then
    docker pull elasticsearch
    docker run -d --name "$DOCKER_NAME_PREFIX"elasticsearch -e "ES_JAVA_OPTS=-Xms512m -Xmx512m" elasticsearch
    { sleep 2; docker ps | grep -q "$DOCKER_NAME_PREFIX"elasticsearch; } || { echo "Elasticsearch failed to run!"; exit 1; }
    ip=`docker inspect "$DOCKER_NAME_PREFIX"elasticsearch | grep IPAddress | tail -1 | cut -d'"' -f4`
    echo "Elasticsearch IP: ${ip}"
    ELASTICSEARCH_HOST="$ip"
fi

if [ -z "$LATEX_HOST" ]; then
    docker pull "$DOCKER_IMAGE_PREFIX"latex
    docker run -d --name "$DOCKER_NAME_PREFIX"latex latex
    { sleep 2; docker ps | grep -q "$DOCKER_NAME_PREFIX"latex; } || { echo "Latex failed to run!"; exit 1; }
    ip=`docker inspect "$DOCKER_NAME_PREFIX"latex | grep IPAddress | tail -1 | cut -d'"' -f4`
    echo "Latex IP: ${ip}"
    LATEX_HOST="$ip"
fi

# Start nginx
docker pull "$DOCKER_IMAGE_PREFIX"nginx
docker run --add-host "$USE_HOST":127.0.0.1 -v "`pwd`":/var/www/"$USE_HOST" -p 0.0.0.0:80:80 \
    --name "$DOCKER_NAME_PREFIX"nginx -d "$DOCKER_IMAGE_PREFIX"nginx
{ sleep 2; docker ps | grep -q "$DOCKER_NAME_PREFIX"nginx; } || { echo "nginx failed to run!"; exit 1; }
ip=`docker inspect "$DOCKER_NAME_PREFIX"nginx | grep IPAddress | tail -1 | cut -d'"' -f4`
echo "nginx IP: ${ip}"

if [ -z "$SELENIUM_HOST" ]; then
    stag="3.4.0-einsteinium"
    docker run -d --add-host "$USE_HOST":"$ip" --name "$DOCKER_NAME_PREFIX"selenium_hub selenium/hub:"$stag"
    docker run -d --add-host "$USE_HOST":"$ip" --name "$DOCKER_NAME_PREFIX"selenium_chrome \
        --link "$DOCKER_NAME_PREFIX"selenium_hub:hub selenium/node-chrome:"$stag"
    docker run -d --add-host "$USE_HOST":"$ip" --name "$DOCKER_NAME_PREFIX"selenium_firefox \
        --link "$DOCKER_NAME_PREFIX"selenium_hub:hub selenium/node-firefox:"$stag"
    { sleep 2; docker ps | grep -q "$DOCKER_NAME_PREFIX"selenium_hub; } || { echo "Selenium failed to run!"; exit 1; }
    ip=`docker inspect "$DOCKER_NAME_PREFIX"selenium_hub | grep IPAddress | tail -1 | cut -d'"' -f4`
    echo "Selenium IP: ${ip}"
    SELENIUM_HOST="$ip"
fi

echo
echo "// ---------------------------- //"
echo "//  Docker containers started!  //"
echo "// ---------------------------- //"
echo

CGI="/var/www/${USE_HOST}/cgi"
EXEC="docker exec -i ${DOCKER_NAME_PREFIX}nginx"
$EXEC "$CGI"/../deploy.sh
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI global.default_host "$USE_HOST"
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI mailgun.domain "$USE_HOST"
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI elasticsearch.hosts '['"$ELASTICSEARCH_HOST"']'
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI latex.hosts '['"$LATEX_HOST"']'
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI selenium.host "$SELENIUM_HOST"
MYDB=`$EXEC php "$CGI"/bootstrap.php config global.db`
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI "$MYDB".host "$MARIADB_HOST"
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI "$MYDB".user "$MARIADB_USER"
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI "$MYDB".pass "$MARIADB_PASS"
$EXEC php "$CGI"/bootstrap.php ini_file_set LOCAL_INI "$MYDB".dbname "$MARIADB_DBNAME"
