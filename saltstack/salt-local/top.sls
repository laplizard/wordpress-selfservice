base:
  'dev':
    - apache
    - mysql
    #- docker
    #- wordpress.docker
    - php
    - php.mysql
    - php-mysql-restart
    - php-wordpress-dev
    # apache virtual hosts and location aliases, including selfservice and sites
    - selfservice.vhosts.standard
    - selfservice.master
    # selfservice site installs
    - selfservice.sites.wordpress
