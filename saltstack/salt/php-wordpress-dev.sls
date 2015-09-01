# wordpress dev stuff

include:
  - php.composer

php-wp-coding-standards:
  cmd.run:
   - name: composer create-project wp-coding-standards/wpcs:dev-master --no-dev --keep-vcs
   - cwd: /srv
   - creates: /srv/wpcs/vendor/bin/phpcs
   - require:
      - cmd: install-composer

/usr/local/bin/phpcs:
  file.symlink:
   - target: /srv/wpcs/vendor/bin/phpcs
   - requires:
      - cmd: php-wp-coding-standards

# dev database
wordpress-dev db:
  mysql_database.present:
   - name: wordpress-dev

wordpress-dev user:
  mysql_user.present:
   - name: wordpress-dev
   - allow_passwordless: True
   - host: localhost

wordpress-dev grant:
  mysql_grants.present:
   - user: wordpress-dev
   - database: wordpress-dev.*
   - host: localhost
   - grant: select,insert,update,delete,create,drop,alter,index
   - require:
      - mysql_database: wordpress-dev
      - mysql_user: wordpress-dev

