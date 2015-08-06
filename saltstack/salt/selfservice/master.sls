
selfservice-db:
  mysql_database.present:
   - name: selfservice

selfservice-user:
  mysql_user.present:
   - name: selfservice
   - host: 'localhost'
   - password: {{ salt['selfservice.password']('selfservice','selfservice-db') }}

selfservice-grant:
  mysql_grants.present:
   - user: selfservice
   - database: selfservice.*
   - host: 'localhost' # '%'
   - grant: select,insert,update,delete,create,drop,alter,index
   - require: 
      - mysql_database: selfservice
      - mysql_user: selfservice

selfservice-dir:
  file.directory:
   - name: /var/www/html/selfservice
   - user: www-data
   - group: www-data
   - mode: 755

selfservice-download:
  cmd.run:
   - require:
     - docker: cgreenhalgh/wp-cli
     - file: /var/www/html/selfservice
   - name: docker run --rm -v /var/www/html/selfservice:/var/www/html cgreenhalgh/wp-cli sudo -u www-data wp core download
   - unless: ls /var/www/html/selfservice/wp-includes/version.php


