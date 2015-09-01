# restart apache2 if php-mysql installed
{% from "apache/map.jinja" import apache with context %}

php-mysql-restart:
  module.wait:
   - name: service.restart
   - m_name: {{ apache.service }}
   - watch:
      - pkg: php-mysql

