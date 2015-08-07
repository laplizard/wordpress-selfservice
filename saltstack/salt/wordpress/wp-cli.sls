wp-cli:
  file.managed:
   - name: /usr/local/bin/wp
   - source: https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
   - source_hash: https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar.md5
   - user: root
   - group: root
   - mode: 755

