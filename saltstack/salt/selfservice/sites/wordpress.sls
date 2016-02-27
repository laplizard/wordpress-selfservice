# wordpress
{% set wpversion = '4.3' %}
{% set wphash = 'sha1=1e9046b584d4eaebac9e1f7292ca7003bfc8ffd7' %}

selfservice-cache-wp{{ wpversion }}:
  file.managed:
   - name: /srv/cache/wordpress-{{ wpversion }}.tar.gz
   - source: https://wordpress.org/wordpress-{{ wpversion }}.tar.gz
   - source_hash: {{ wphash }}
   - makedirs: True
   - user: root
   - group: root
   - mode: 644
   - dir_mode: 755

{% for htmldir,site in salt['pillar.get']('selfservice:sites', {}).items() %}
{% if site.get('type','') == 'wordpress' %}
{% if site.get('status','publish') != 'publish' %}
{# not published... ?? #}
{% else %}

{% set instance = site.get('id',htmldir) %}
{% set admin_password_hash = site.get('admin_password_hash','') %}
{% set admin_email = site.get('admin_email',salt['pillar.get']('selfservice:email','root@127.0.0.1')) %}
{% set title = site.get('title','Unnamed WPSS site') %}
{% set description = site.get('description', 'Undescribed WPSS site' ) %}
{% set url = site.get('url', '') %}

# Steve: next line would be to get template name... for choosing which state template to include - below.
#{% set template_name = site.get('template','') %}

# interface could be docker0, except doesn't exist if docker not yet installed!
{% set dbinterface = 'eth0' %}

selfservice-db-wp{{ instance }}:
  mysql_database.present:
   - name: wp{{ instance }}

selfservice-user-wp{{ instance }}:
  mysql_user.present:
   - name: wp{{ instance }}
   - host: '%'
   - password: {{ salt['selfservice.password'](instance,instance+'-db') }}

selfservice-grant-wp{{ instance }}:
  mysql_grants.present:
   - user: wp{{ instance }}
   - database: wp{{ instance }}.*
   - host: '%' # access from docker VM for management
   - grant: select,insert,update,delete,create,drop,alter,index
   - require: 
      - mysql_database: wp{{ instance }}
      - mysql_user: wp{{ instance }}

selfservice-dir-{{ instance }}:
  file.directory:
   - name: {{ htmldir }}
   - user: www-data
   - group: www-data
   - mode: 755
   - makedirs: True


# **************** Steve start

# Or for for testing purposes, before passing template via pillar is working:
{% set template_name = 'artcode' %}
#{% set template_name = 'buddypress' %}
#{% set template_name = 'buddypress_artcode' %}

# Now, include the salt state template for the template name...
template-include-{{ instance }}:
include:
  - {{ template_name }} #without .sls extension

# **************** Steve end


selfservice-download-{{ instance }}:
  cmd.run:
   - require:
     - file: /srv/cache/wordpress-{{ wpversion }}.tar.gz
     #- file: /usr/local/bin/wp
     - file: {{ htmldir }}
   - name: tar zxf /srv/cache/wordpress-{{ wpversion }}.tar.gz --strip-components=1 wordpress
   - user: www-data
   - group: www-data
   - cwd: {{ htmldir }}
   - unless: ls {{ htmldir }}/wp-includes/version.php

selfservice-keys-{{ instance }}:
  cmd.run:
    - name: /usr/bin/curl -s -o {{ htmldir }}/wp-keys.php https://api.wordpress.org/secret-key/1.1/salt/ && /bin/sed -i "1i\\<?php" {{ htmldir }}/wp-keys.php && chown www-data:www-data {{ htmldir }}/wp-keys.php
    - creates: {{ htmldir }}/wp-keys.php
    - require_in:
      - file: {{ htmldir }}/wp-config.php

selfservice-config-{{ instance }}:
  file.managed:
   - require:
      - cmd: selfservice-download-{{ instance }}
   - name: {{ htmldir }}/wp-config.php
   - source: salt://selfservice/files/wp-config.php
   - user: www-data
   - group: www-data
   - mode: 600
   - template: jinja
   - context: 
       instance: wp{{ instance }}
       dbpassword: {{ salt['selfservice.password'](instance,instance+'-db') }}
       dbhost: {{ salt['network.interface_ip'](dbinterface) }} # not localhost if from vm!

selfservice-install-{{ instance }}:
  cmd.run:
   - require:
      #- file: /usr/local/bin/wp
      - file: {{ htmldir }}/wp-config.php
   - unless: sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} core is-installed 
   - name:  sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} core install --url={{ salt['selfservice.safetext'](url) }} "--title={{ salt['selfservice.safetext'](title) }}" --admin_user=admin --admin_email={{ salt['selfservice.safetext'](admin_email) }} --admin_password={{  salt['selfservice.password'](instance,instance+'-wp') }}

# update description - blogdescription
# require registration to comment - comment_registration
# dont ping update services on publishing - ping_sites

{% set options = { 'blogname': title, 'siteurl': url, 'home': url, 'blogdescription': description, 'admin_email': admin_email, 'comment_registration':'1', 'ping_sites':'' } %}
{% for oname,ovalue in options.iteritems() %}

selfservice-options-{{ instance }}-{{oname}}:
  cmd.run:
   - unless: "[ \"`sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} option get {{oname}}`\" == \"{{ salt['selfservice.safetext'](ovalue) }}\" ]"
   #- require:
   #   - file: /usr/local/bin/wp
   - name: sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} option set {{oname}} "{{ salt['selfservice.safetext'](ovalue) }}"


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

# admin password - overwrite from selfservice account
# tricky to check as DB may not exist when this SLS is read
selfservice-admin-{{ instance }}:
  module.run:
   - name: mysql.query
   - database: wp{{ instance }}
   - query: "UPDATE wp_users SET user_pass='{{ admin_password_hash }}' WHERE id=1"
   - require:
      - cmd: selfservice-install-{{ instance }}
   #- unless: ??

{% endif %}{# published #}
{% endif %}{# wordpress #}
{% endfor %}





