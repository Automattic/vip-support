#!/usr/bin/env bash

if [ $# -lt 2 ]; then
	echo "usage: $0 <db-create-user> <db-create-pass> [db-create-host]"
	exit 1
fi

# These are the DB user/pass combo to CREATE
# the database, not to use the database created
# For e.g. these creds might be the MySQL root user
DB_USER=$1
DB_PASS=$2
DB_HOST=$3

install_db() {
	# Parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

    # Create database
    mysql -e "DROP DATABASE IF EXISTS wordpress_behat_test; CREATE DATABASE wordpress_behat_test; GRANT ALL PRIVILEGES ON wordpress_behat_test.* TO 'wp_bh_test'@'localhost' IDENTIFIED BY 'wp_bh_test';" --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

composer install
install_db

exit 0