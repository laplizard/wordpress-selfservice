# apache formula, 127.0.0.1 as (dev) virtual host
apache:
# apache-formula Debian default is /srv
  lookup:
{% if salt['grains.get']('os','') == 'Ubuntu' %}
    wwwdir: /var/www
{% endif %}
  sites:
    127.0.0.1:
      ServerName: 127.0.0.1

