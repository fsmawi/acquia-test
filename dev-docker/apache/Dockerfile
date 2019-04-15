FROM ubuntu:xenial

RUN apt-get update && apt-get install -y \
    software-properties-common sudo \
    && rm -rf /var/lib/apt/lists/*

RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php \
    && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y \
  ntp \
  ntpdate \
  net-tools \
  libssh2-1-dev \
  apache2 \
  curl \
  graphviz \
  mysql-client \
  openssh-client \
  openssh-server \
  sqlite \
  vim \
  nano \
  zip \
  unzip \
  git

RUN apt-get install -y \
  php5.6 \
  php5.6-curl \
  php5.6-cli \
  php5.6-fpm \
  php5.6-gd \
  php5.6-gmp \
  php5.6-mbstring \
  php5.6-mysql \
  php5.6-sqlite \
  php5.6-xdebug \
  php5.6-xml \
  php-pear \
  php5.6-dev

# Configure Apache:
ADD ./001-wip-service.conf /etc/apache2/sites-available/
RUN a2ensite 001-wip-service.conf \
  && a2dissite 000-default.conf \
  && a2enmod rewrite \
  && a2enmod proxy proxy_fcgi \
  && a2enconf php5.6-fpm \
  # Avoid apache complaining about missing ServerName and timezone config:
  && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure PHP:
RUN echo "date.timezone=UTC" | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  && echo "always_populate_raw_post_data=-1" | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  # Error logging:
  && echo 'log_errors=on' \
    | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  && echo 'error_log=/tmp/php-errors.log' \
    | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  && echo 'error_reporting=E_ALL' \
    | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  && echo 'memory_limit=256M' \
    | tee -a /etc/php/5.6/fpm/php.ini /etc/php/5.6/cli/php.ini \
  && echo 'display_errors=stderr' >> /etc/php/5.6/cli/php.ini \
 # && echo 'opcache.revalidate_freq = 0' >> /etc/php5/fpm/conf.d/05-opcache.ini \
  && sed -i "s~listen = /run/php/php5.6-fpm.sock~listen = 127.0.0.1:9000~" /etc/php/5.6/fpm/pool.d/www.conf \
  && mkdir -p /var/run/php/ \
  && sed -i "s~pid = /run/php/~pid = /var/run/php/~" /etc/php/5.6/fpm/php-fpm.conf \
  && echo "" | pecl install -f ssh2 && \
  echo "extension=ssh2.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`


# Required for openssh-server to start:
RUN mkdir /var/run/sshd \
  # Add the dev user and the dev.dev alias:
  && adduser --disabled-password --gecos "" --uid 1000 dev \
  && useradd --non-unique --shell=/bin/bash --home-dir=/home/dev --uid 1000 --gid 1000 dev.dev \

  # Set up the default SSH identity and authorize it for local SSH login:
  && sudo -u dev mkdir -p /home/dev/.ssh \
  && chmod 700 /home/dev/.ssh \
  && sudo -u dev ssh-keygen -q -P "" -b 2048 -f /home/dev/.ssh/id_rsa \
  && sudo -u dev touch /home/dev/.ssh/authorized_keys \
  && chmod 600 /home/dev/.ssh/authorized_keys \
  && sudo -u dev cat /home/dev/.ssh/id_rsa.pub >> /home/dev/.ssh/authorized_keys \

  # Allow root to ssh to localhost as dev (this should be replaced by mocking):
  && ssh-keygen -q -P "" -b 2048 -f /root/.ssh/id_rsa \
  && touch /root/.ssh/authorized_keys \
  && chmod 600 /root/.ssh/authorized_keys \
  && cat /root/.ssh/id_rsa.pub >> /home/dev/.ssh/authorized_keys \

  # Create files required by Wip:
  && mkdir /mnt/tmp && chmod 777 /mnt/tmp \
  && touch /tmp/threadpool.log && chmod 666 /tmp/threadpool.log \
  && mkdir -p /mnt/files/dev.dev/nobackup \
  && chown -R dev:www-data /mnt/files \
  && chmod -R 775 /mnt/files \
  && mkdir /wip && chmod 755 /wip \

  # Start the monitor-daemon on cron, every 5 minutes:
  && (crontab -u dev -l 2> /dev/null; \
    echo "*/5 * * * * /var/www/html/bin/wipctl monitor-daemon start 2>&1 > /dev/null") | \
    crontab -u dev - \

  # Set some environment variables for all users:
  && echo "AH_SITE_GROUP=dev" >> /etc/environment \
  && echo "AH_SITE_ENVIRONMENT=dev" >> /etc/environment \
  && echo "WIP_CONTAINERIZED=1" >> /etc/environment \
  && echo "WIP_SERVICE_OVERRIDE_DATABASE=1" >> /etc/environment \

  # Set up some shell niceties:
  && echo "export EDITOR=vim" | tee -a /home/dev/.bashrc /root/.bashrc \
  && echo 'export PATH=/var/www/html/bin:$PATH' | tee -a /home/dev/.bashrc /root/.bashrc \
  && echo 'export PS1="\\n\[\e[0;32m\]\u@dev-docker-\h:\w\\n\$\[\e[0m\] "' | tee -a /home/dev/.bashrc /root/.bashrc \
  && echo '"\e[A": history-search-backward' | tee -a /home/dev/.inputrc /root/.inputrc \
  && echo '"\e[B": history-search-forward' | tee -a /home/dev/.inputrc /root/.inputrc \

  # Configure vim:
  && echo "colorscheme slate" >> /etc/vim/vimrc

COPY config.global.dev.yml /wip/config/
COPY config.factory.cfg /wip/config/
COPY entrypoint.sh /
COPY composer_installer.sh /tmp/
RUN chmod +x /tmp/composer_installer.sh

ENV TERM=xterm
ENV AH_SITE_GROUP dev
ENV AH_SITE_ENVIRONMENT dev
ENV WIP_CONTAINERIZED 1
ENV WIP_SERVICE_OVERRIDE_DATABASE 1

EXPOSE 80

# Make an Acquia-hosting-like path to the docroot.
RUN mkdir -p /mnt/www/html/${AH_SITE_GROUP}.${AH_SITE_ENVIRONMENT} \
    && ln -s /var/www/html /mnt/www/html/${AH_SITE_GROUP}.${AH_SITE_ENVIRONMENT}/docroot

WORKDIR /var/www/html

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/bash"]
