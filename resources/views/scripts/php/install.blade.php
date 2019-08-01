# Install Base PHP Packages

apt-add-repository ppa:ondrej/php -y

apt-get update

apt-get install -y --force-yes php7.1-bcmath \
    php7.1-cli \
    php7.1-curl \
    php7.1.dev \
    php7.1-fpm \
    php7.1-gd \
    php7.1-imap \
    php7.1-intl \
    php7.1-mbstring \
    php7.1-memcached \
    php7.1-mcrypt \
    php7.1-mysql \
    php7.1-pgsql \
    php7.1-readline \
    php7.1-soap \
    php7.1-sqlite3 \
    php7.1-xml \
    php7.1-zip

# Install Composer Package Manager

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Configure PHP CLI

cat > /etc/php/7.1/cli/php.ini << EOF
{!! file_get_contents(resource_path('views/scripts/php/cli.ini')) !!}

EOF

# Configure PHP FPM

cat > /etc/php/7.1/fpm/php.ini << EOF
{!! file_get_contents(resource_path('views/scripts/php/fpm.ini')) !!}

EOF

# Configure FPM Pool

cat > /etc/php/7.1/fpm/pool.d/www.conf << EOF
{!! file_get_contents(resource_path('views/scripts/php/www.conf')) !!}

EOF

# Restart FPM

service php7.1-fpm restart > /dev/null 2>&1

# Configure Sudoers Entries

echo "cloud ALL=NOPASSWD: /usr/sbin/service php7.1-fpm reload" > /etc/sudoers.d/php-fpm
