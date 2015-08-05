# more this to an execution module...
#{% import hashlib %}
#{% m = hashlib.md5() %}
#{% m.update( salt['pillar.get]('selfservice:seed','E4cq8KcZ') %}
#{% m.update( 'db' ) %}

selfservice-db:
  mysql_database.present:
   - name: selfservice

selfservice-user:
  mysql_user.present:
   - name: selfservice
   - host: 'localhost'
   #- password: {{ m.digest() }}
