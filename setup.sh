# dependencies of MediaWiki
sudo apt-get install apache2 default-mysql-server php libapache2-mod-php php-mysql php-intl php-xml php-mbstring php-curl php-wikidiff2 imagemagick librsvg2-bin lame
# dependencies of our system
sudo apt-get install git composer npm unzip rdfind curl

# Node 14
sudo curl -s https://deb.nodesource.com/gpgkey/nodesource.gpg.key | sudo apt-key add -
sudo sh -c "echo deb https://deb.nodesource.com/node_14.x impish main > /etc/apt/sources.list.d/nodesource.list"
sudo apt-get update
sudo apt-get install nodejs
# Update NPM
sudo npm install -g npm@latest
# Let www-data run NPM
sudo mkdir /var/www/.npm /var/www/.config
sudo chown www-data: /var/www/.npm /var/www/.config
# We used to run NPM as root
sudo chown -R www-data: node_modules

# Docker
# https://docs.docker.com/engine/install/debian/#install-using-the-convenience-script
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
# ElasticSearch
docker run -d --restart=always \
  -v elasticdata:/usr/share/elasticsearch/data \
  -e "discovery.type=single-node" \
  -e "bootstrap.system_call_filter=false" \
  -p 9200:9200 \
  -p 9300:9300 \
  docker-registry.wikimedia.org/dev/stretch-elasticsearch:0.1.0

# create master copies of repositories
sudo -u www-data mkdir repositories
cd repositories
while IFS=' ' read -r repo dir; do
	sudo -u www-data git clone --no-checkout https://gerrit.wikimedia.org/r/$repo.git $repo
done < ../repository-lists/all.txt
cd ..

# Composer wants a directory for itself (COMPOSER_HOME)
sudo -u www-data mkdir composer

# Create folder for wikis
sudo -u www-data mkdir wikis

# Create a database user that is allowed to create databases for each wiki,
# and the central patchdemo database
sudo mysql -u root --password='' < sql/user.sql
# Create the central patchdemo database
sudo mysql -u patchdemo --password='patchdemo' < sql/patchdemo.sql

# dependencies for the website
composer install --no-dev
sudo -u www-data npm ci --production

# setup daily cron job to deduplicate files
echo "#!/bin/bash
$(readlink -f deduplicate.sh)" > /etc/cron.daily/patchdemo-deduplicate
chmod u+x /etc/cron.daily/patchdemo-deduplicate
# setup monthly cron job to optimize databases and free disk space
echo "#!/bin/bash
sudo mysqlcheck --optimize --all-databases -u root --password=''" > /etc/cron.monthly/patchdemo-optimize
chmod u+x /etc/cron.monthly/patchdemo-optimize

# PHP settings
echo "
; set session expiration to a month (default is 24 minutes???), cookie expiration too
session.gc_maxlifetime = 2592000
session.cookie_lifetime = 2592000

; double the default memory limit
memory_limit = 256M
" > /etc/php/7.4/apache2/conf.d/patchdemo.ini

# enable .htaccess files
echo "<Directory /var/www/html>
Options -Indexes
AllowOverride All
</Directory>" > /etc/apache2/sites-available/patchdemo.conf

# Support Parsoid URLs for pages with slashes in the title.
#
# https://www.mediawiki.org/wiki/Extension:VisualEditor#Troubleshooting
# This has to be set for each virtualhost (it doesn't work when set at server level while using
# virtualhosts), so we have to edit the 000-default file, where the default (and only) virtualhost
# is defined. This is extremely stupid behavior from Apache, and the Byzantine configuration
# utilities provided by Debian/Ubuntu turn out to be entirely incapable of handling it.
#
# So, here goes grep and sed.
grep -q "AllowEncodedSlashes NoDecode" /etc/apache2/sites-available/000-default.conf ||
	sed -i "/<\/VirtualHost>/i\
	AllowEncodedSlashes NoDecode" /etc/apache2/sites-available/000-default.conf

sudo a2ensite patchdemo
# enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
