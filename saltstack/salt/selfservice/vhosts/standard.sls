# strongly based on apache-formula apache/vhosts/standard.sls
# with addition of multiple Locations with Aliases

{% from "apache/map.jinja" import apache with context %}

include:
  - apache

{% for id, site in salt['pillar.get']('apache:sites', {}).items() %}
{% set documentroot = site.get('DocumentRoot', '{0}/{1}'.format(apache.wwwdir, id)) %}

{% for path, location in site.get('locations',{}).items() %}
{% set safepath = path.replace("/","-") %}
{% set locid = location.get('id', safepath) %}

{{ id }}-{{ locid }}:
  file:
    - managed
    - name: {{ apache.vhostdir }}/{{ id }}-{{ locid }}.location
{% if location.get('available',true) %}
    - source: {{ location.get('template_file', 'salt://selfservice/vhosts/location.tmpl') }}
{% else %}
    - source: salt://selfservice/vhosts/location-unavailable.tmpl
{% endif %}
    - template: {{ location.get('template_engine', 'jinja') }}
    - context:
        id: {{ path|json }}
        location: {{ location|json }}
        map: {{ apache|json }}
    - require:
      - pkg: apache
    - watch_in:
      - module: apache-reload

{% endfor %}

{{ id }}:
  file:
    - managed
    - name: {{ apache.vhostdir }}/{{ id }}{{ apache.confext }}
    - source: {{ site.get('template_file', 'salt://selfservice/vhosts/standard.tmpl') }}
    - template: {{ site.get('template_engine', 'jinja') }}
    - context:
        id: {{ id|json }}
        site: {{ site|json }}
        map: {{ apache|json }}
    - require:
      - pkg: apache
    - watch_in:
      - module: apache-reload

{{ id }}-documentroot:
  file.directory:
    - unless: test -d {{ documentroot }}
    - name: {{ documentroot }}
    - makedirs: True

{% if grains.os_family == 'Debian' %}
a2ensite {{ id }}{{ apache.confext }}:
  cmd:
    - run
    - unless: test -f /etc/apache2/sites-enabled/{{ id }}{{ apache.confext }}
    - require:
      - file: /etc/apache2/sites-available/{{ id }}{{ apache.confext }}
    - watch_in:
      - module: apache-reload
{% endif %}

{% endfor %}
