
# Install MySQL

debconf-set-selections <<< "mysql-community-server mysql-community-server/data-dir select ''"
debconf-set-selections <<< "mysql-community-server mysql-community-server/root-pass password {!! $databasePassword !!}"
debconf-set-selections <<< "mysql-community-server mysql-community-server/re-root-pass password {!! $databasePassword !!}"

apt-get install -y mysql-server

# Configure Password Expiration

echo "default_password_lifetime = 0" >> /etc/mysql/mysql.conf.d/mysqld.cnf

# Configure Access Permissions For Root & Cloud Users

sed -i '/^bind-address/s/bind-address.*=.*/bind-address = */' /etc/mysql/mysql.conf.d/mysqld.cnf

mysql --user="root" --password="{!! $databasePassword !!}" -e "GRANT ALL ON *.* TO root@'localhost' IDENTIFIED BY '{!! $databasePassword !!}';"
mysql --user="root" --password="{!! $databasePassword !!}" -e "GRANT ALL ON *.* TO root@'%' IDENTIFIED BY '{!! $databasePassword !!}';"

service mysql restart

mysql --user="root" --password="{!! $databasePassword !!}" -e "CREATE USER 'cloud'@'localhost' IDENTIFIED BY '{!! $databasePassword !!}';"
mysql --user="root" --password="{!! $databasePassword !!}" -e "CREATE USER 'cloud'@'%' IDENTIFIED BY '{!! $databasePassword !!}';"
mysql --user="root" --password="{!! $databasePassword !!}" -e "GRANT ALL ON *.* TO 'cloud'@'localhost' IDENTIFIED BY '{!! $databasePassword !!}' WITH GRANT OPTION;"
mysql --user="root" --password="{!! $databasePassword !!}" -e "GRANT ALL ON *.* TO 'cloud'@'%' IDENTIFIED BY '{!! $databasePassword !!}' WITH GRANT OPTION;"
mysql --user="root" --password="{!! $databasePassword !!}" -e "FLUSH PRIVILEGES;"

# Create The Initial Database

mysql --user="root" --password="{!! $databasePassword !!}" -e "CREATE DATABASE cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Install & Configure Redis Server

apt-get install -y --force-yes redis-server
sed -i 's/bind 127.0.0.1/bind 0.0.0.0/' /etc/redis/redis.conf
service redis-server restart

# Install & Configure Memcached

apt-get install -y --force-yes memcached
sed -i 's/-l 127.0.0.1/-l 0.0.0.0/' /etc/memcached.conf
service memcached restart

# Install & Configure Beanstalk

apt-get install -y --force-yes beanstalkd
sed -i "s/BEANSTALKD_LISTEN_ADDR.*/BEANSTALKD_LISTEN_ADDR=0.0.0.0/" /etc/default/beanstalkd
sed -i "s/#START=yes/START=yes/" /etc/default/beanstalkd

# Reload Beanstalk To Pull In New Configuration

service beanstalkd start
sleep 5
service beanstalkd restart

# Install AWS CLI (For S3 Backups)

apt-get install -y --force-yes python-pip

pip install awscli
