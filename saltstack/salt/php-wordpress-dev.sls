# wordpress dev stuff

include:
  - php.composer

php-codesniffer:
  cmd.run:
   - name: composer create-project wp-coding-standards/wpcs:dev-master --no-dev
   - cwd: /srv
   - creates: /srv/wpcs/vendor/bin/phpcs
   - require:
      - cmd: install-composer
  
