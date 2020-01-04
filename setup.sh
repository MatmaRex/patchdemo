# dependencies of MediaWiki
sudo apt-get install apache2 mysql-server php libapache2-mod-php php-mysql php-intl php-xml php-mbstring php-curl
# dependencies of our system
sudo apt-get install git composer rdfind

# create master copies of repositories
sudo -u www-data mkdir repositories
cd repositories
while IFS=' ' read -r repo dir; do
	sudo -u www-data git clone --no-checkout https://gerrit.wikimedia.org/r/$repo.git $repo
done < ../repositories.txt
cd ..


# Composer wants a directory for itself (COMPOSER_HOME)
sudo -u www-data mkdir composer

# create a database user that is allowed to create databases for each wiki
sudo mysql -u root --password='' -e "
CREATE USER 'patchdemo'@'localhost' IDENTIFIED BY 'patchdemo';
GRANT ALL PRIVILEGES ON \`patchdemo\_%\`.* TO 'patchdemo'@'localhost';
"
