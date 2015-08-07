php-for-wordpress:
  docker.built:
   - name: cgreenhalgh/php-for-wordpress
   - path: /var/docker/php-for-wordpress
   - watch:
      - file: /var/docker/php-for-wordpress/Dockerfile

# copy of docker files
/var/docker/php-for-wordpress/Dockerfile:
  file.managed:
   - source: salt://wordpress/docker/files/php-for-wordpress/Dockerfile
   - user: root
   - grop: root
   - mode: 444
   - makedirs: True
   - dir_mode: 755

docker-wp-cli:
  docker.built:
   - name: cgreenhalgh/wp-cli
   - path: /var/docker/wp-cli
   - watch:
      - file: /var/docker/wp-cli/Dockerfile

# copy of docker files
/var/docker/wp-cli/Dockerfile:
  file.managed:
   - source: salt://wordpress/docker/files/wp-cli/Dockerfile
   - user: root
   - grop: root
   - mode: 444
   - makedirs: True
   - dir_mode: 755

