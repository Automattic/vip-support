#!/bin/bash

# called by Travis CI

# Exit if anything fails AND echo each command before executing
# http://www.peterbe.com/plog/set-ex
set -ex

# Install unit tests
# ==================

bash bin/install-wp-tests.sh wordpress_test wordpress password localhost "${WP_VERSION}"

# Install Behat tests
# ===================

composer self-update

echo 'date.timezone = "Europe/London"' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

mkdir -p $WORDPRESS_FAKE_MAIL_DIR

# Set up the databases
sudo service mysql restart
mysql -e 'CREATE DATABASE wordpress;' -uroot
mysql -e 'GRANT ALL PRIVILEGES ON wordpress.* TO "wordpress"@"localhost" IDENTIFIED BY "password"' -uroot

exit 0

# http://docs.travis-ci.com/user/languages/php/#Apache-%2B-PHP

sudo apt-get install apache2 libapache2-mod-fastcgi

# Parallel for running PHPLint quicker
sudo apt-get install parallel

# enable php-fpm
# Get the WORDPRESS_FAKE_MAIL_DIR into PHP as an environment variable
echo "env[WORDPRESS_FAKE_MAIL_DIR] = ${WORDPRESS_FAKE_MAIL_DIR}" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default
sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
sudo a2enmod rewrite actions fastcgi alias
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

# configure apache virtual hosts
# @TODO Allow HTTPS connections (need a solution which doesn't mind self-signed certs)
sudo cp -f $TRAVIS_BUILD_DIR/ci/wordpress-apache.conf /etc/apache2/sites-available/default
sudo sed -e "s?%WORDPRESS_SITE_DIR%?${WORDPRESS_SITE_DIR}?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart

# @TODO Allow a user to add their GitHub token, encrypted, so they can authenticate with GitHub and bypass API limits applied to Travis as a whole
# https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens
# http://awestruct.org/auto-deploy-to-github-pages/ and scroll to "gem install travis"
composer update --no-interaction --prefer-dist

# install WordPress
mkdir -p $WORDPRESS_SITE_DIR
cd $WORDPRESS_SITE_DIR
# @TODO Figure out how to deal with installing "trunk", SVN checkout?
$WP_CLI core download
# @TODO Set WP_DEBUG and test for notices, etc
$WP_CLI core config --dbname=wordpress --dbuser=wordpress --dbpass=password <<PHP
define( 'WORDPRESS_FAKE_MAIL_DIR', '${WORDPRESS_FAKE_MAIL_DIR}' );
PHP
$WP_CLI core install --url=local.wordpress.dev --title="WordPress Testing" --admin_user=admin --admin_password=password --admin_email=testing@example.invalid

# Make MU plugins
mkdir -p $WORDPRESS_SITE_DIR/wp-content/mu-plugins/

# Copy the plugin into MU plugins
cp -pr $TRAVIS_BUILD_DIR $WORDPRESS_SITE_DIR/wp-content/mu-plugins/
ls -al $WORDPRESS_SITE_DIR/wp-content/mu-plugins/

# Copy the No Mail MU plugin into place
cp -pr $TRAVIS_BUILD_DIR/features/bootstrap/fake-mail.php $WORDPRESS_SITE_DIR/wp-content/mu-plugins/

cat <<EOT >> $WORDPRESS_SITE_DIR/wp-content/mu-plugins/vip-support-bootstrap.php
<?php
/**
 * Plugin Name:  WordPress.com VIP Support (MU)
 * Plugin URI:   https://vip.wordpress.com/documentation/contacting-vip-hosting/
 * Description:  Manages the WordPress.com Support Users on your site
 * Version:      1.0
 * Author:       <a href="http://automattic.com">Automattic</a>
 * License:      GPLv2 or later
 */

require_once( dirname( __FILE__ ) . '/${WORDPRESS_TEST_SUBJECT}/vip-support.php' );

EOT