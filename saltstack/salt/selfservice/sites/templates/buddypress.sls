# Steve note: rather than copying the plugin dir, this could be a file-managed tarball or zip.
# However, I found that this caused problems with the source_hash, when the plugin developer makes changes, there is a risk of hash mismatches.

 # ******** Start plugin
 
{% set plugin_name = 'buddypress' %}
		
{{ plugin_name }}-plugin-copy:
  cmd.run:
   - require: 
      - cmd: selfservice-install
   - name: sudo cp -R /srv/wordpress-selfservice/plugins/{{ plugin_name }} {{ htmldir }}/wp-content/plugins
 #  - unless: ls {{ htmldir }}/wp-content/plugins/{{ plugin_name }}
 
   # Steve: following uses WP-CLI to activate plugin
{{ plugin_name }}-plugin-activate:
  cmd.run:
   - require: 
      - cmd: {{ plugin_name }}-plugin-copy
   - name:  sudo -u www-data /usr/local/bin/wp --path={{ htmldir }} plugin activate selfservice
   # - unless: ???

 # ******** End plugin
