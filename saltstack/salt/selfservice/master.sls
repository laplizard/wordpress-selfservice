{% set htmldir = '/var/www/html/selfservice' %}
{% set instance = 'selfservice' %}
{% set servername = salt['pillar.get']('selfservice:servername','localhost') %}
{% set wppassword = salt['pillar.get']('selfservice:admin_password','admin') %}
{% set email = salt['pillar.get']('selfservice:email','root@localhost') %}
# interface could be docker0, except doesn't exist if docker not yet installed!
{% set dbinterface = 'eth0' %}

include: 
 # from php-formula
 - php.mysql
 # may need to restart apache2 depending on installation order
 - wordpress.wp-cli

# allow selfservice to sudo salt-call event.send
selfservice-sudoers:
  file.managed:
   - name: /etc/sudoers.d/selfservice
   - source: salt://selfservice/files/sudoers
   - user: root
   - group: root
   - mode: 440

selfservice-db:
  mysql_database.present:
   - name: {{ instance }}

selfservice-user:
  mysql_user.present:
   - name: {{ instance }}
   - host: '%'
   - password: {{ salt['selfservice.password'](instance,instance+'-db') }}

selfservice-grant:
  mysql_grants.present:
   - user: {{ instance }}
   - database: {{ instance }}.*
   - host: '%' # access from docker VM for management
   - grant: select,insert,update,delete,create,drop,alter,index
   - require: 
      - mysql_database: {{ instance }}
      - mysql_user: {{ instance }}

selfservice-dir:
  file.directory:
   - name: {{ htmldir }}
   - user: www-data
   - group: www-data
   - mode: 755

# selfservice plugin, release 0.1
selfservice-plugin-cache:
  file.managed:
  # - name: /srv/cache/plugins/wpss-0.2.tar.gz
  # - source: https://github.com/cgreenhalgh/wordpress-selfservice/archive/0.2.tar.gz
   - name: /srv/cache/plugins/wpss-master.tar.gz
   - source: https://github.com/laplizard/wordpress-selfservice/archive/master.tar.gz
#   - source_hash: sha1=8526cf1524696163f583b832924215af3c46fa5e
   - makedirs: True
   - user: root
   - group: root
   - mode: 644
   - dir_mode: 755

selfservice-plugin-install:
  cmd.run:
   - require: 
      - file: selfservice-plugin-cache
      - cmd: selfservice-install
   - user: www-data
   - group: www-data
   # - name: tar zxf /srv/cache/plugins/wpss-0.2.tar.gz --strip-components=2 wordpress-selfservice-0.2/plugins
   - name: tar zxf /srv/cache/plugins/wpss-master.tar.gz --strip-components=2 wordpress-selfservice-master/plugins
   - cwd: {{ htmldir }}/wp-content/plugins
   # - unless: ???

   # Steve: following uses WP-CLI to activate plugin
selfservice-plugin-activate:
  cmd.run:
   - require: 
      - cmd: selfservice-plugin-install
   - name:  sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} plugin activate selfservice
   # - unless: ???

   # Steve: 'wp core download' = WP-CLI for get latest version of WP
selfservice-download:
  cmd.run:
   - require:
     - file: /usr/local/bin/wp
     - file: {{ htmldir }}
   - name: sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} core download
   - unless: ls {{ htmldir }}/wp-includes/version.php

selfservice-keys:
  cmd.run:
    - name: /usr/bin/curl -s -o {{ htmldir }}/wp-keys.php https://api.wordpress.org/secret-key/1.1/salt/ && /bin/sed -i "1i\\<?php" {{ htmldir }}/wp-keys.php && chown www-data:www-data {{ htmldir }}/wp-keys.php
    - creates: {{ htmldir }}/wp-keys.php
    - require_in:
      - file: {{ htmldir }}/wp-config.php

selfservice-config:
  file.managed:
   - require:
      - cmd: selfservice-download
   - name: {{ htmldir }}/wp-config.php
   - source: salt://selfservice/files/wp-config.php
   - user: www-data
   - group: www-data
   - mode: 600
   - template: jinja
   - context: 
       instance: {{ instance }}
       dbpassword: {{ salt['selfservice.password'](instance,instance+'-db') }}
       dbhost: {{ salt['network.interface_ip'](dbinterface) }} # not localhost if from vm!

	   # Steve: if WP is not installed, then install
selfservice-install:
  cmd.run:
   - require:
      - file: /usr/local/bin/wp
      - file: {{ htmldir }}/wp-config.php
   - unless: sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} core is-installed 
   - name:  sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} core install --url=http://{{ servername }}/selfservice/ "--title=Self Service Console for {{ servername }}" --admin_user=admin --admin_password={{ wppassword }} --admin_email={{ email }}

# update description - blogdescription
# require registration to comment - comment_registration
# dont ping update services on publishing - ping_sites

{% set options = { 'blogdescription': 'Research server management', 'comment_registration':'1', 'ping_sites':'' } %}
{% for oname,ovalue in options.iteritems() %}

selfservice-options-{{oname}}:
  cmd.run:
   - unless: "[ \"`sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} option get {{oname}}`\" == \"{{ovalue}}\" ]"
   - require:
      - file: /usr/local/bin/wp
   - name: sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} option set {{oname}} "{{ovalue}}"


{% endfor %}
  
# permalinks setting? option: permalink_structure
# require login for comments
# plugins...?
# ensure NOT open registration
# tagline = option blogdescription
# timezone? option timezone_strong
# language?
# mail server settings? options mainserver_url, mailserver_login, mailserver_pass, mailserver_port
# remote update services notification site default (http://rpc.pingomatic.com/) option: ping_sites
# static front page?
# change discussion settings? e.g. no notify, no link, no comments on new
#   email on comment, moderation:
#   options: (not)require_name_email, comments_notify, default_comment_status (not open), default_ping_status (not open), default_pingback_flag, moderation_notify, comments_registration, show_on_front (posts) 
# cron?

# .htaccess

