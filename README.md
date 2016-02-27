# WordPress Self-Service

WordPress plugin and SaltStack config to allow users to create and manage their own dedicated WordPress instances. 

The idea is that you set up a WordPress instance with this plugin on the Salt master, and it provides a non-developer user interface for people to create and manage their own WordPress instances.

The idea is that instances will be based on admin-managed templates, e.g. specifying combinations of plugins to use, in order to specialise the site for particular purposes.

Use vagrant to build/run the `vagrant-dev` VM to develop/play.

Work in progress...

Chris Greenhalgh, The University of Nottingham, 2015

################################################################

Steve North, The University of Nottingham, 2016:

Working towards being able to select a template from the user interface and each template will determine which plugin, or combination of plugins is installed in the current WordPress instance. 

Firstly , in WPSS there are two ways in which WP gets installed:
  
1. WP Admin (including WPSS plugin) is installed by the state file: 
/srv/wordpress-selfservice/saltstack/salt/selfservice/master.sls

2. WP instances for individuals (including a selection of plugins controlled by state file templates) are installed by the state file: /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/wordpress.sls
 
Clicking 'Publish', 'Update' or 'Move to trash'. on the WP admin installation, websites post type page (should!) update the taxonomy status for the created post. This indicates which template radio-button has been selected (if not selected, then this is set to the 'vanilla' template, with no plugins installed).

The plugin fires off an (empty event) that is interpreted by a Salt Reactor script, causing the wordpress.sls to handle this as required.

If a post has the status publish (and it hasn't already been setup), then a new instance of WP is installed. The post's template name is used to include salt templates, which will in turn copy the appropropriate plugins into the new WP instance (and activate them using WP CLI). 
 
In wordpress.sls it will looks a bit like this:

{% for htmldir,site in salt['pillar.get']('selfservice:sites', {}).items() %}
{% if site.get('type','') == 'wordpress' %}
{% if site.get('status','publish') != 'publish' %}
{# not published... ?? #}
{% else %}

{% set template_name = site.get('template','') %}

{% endif %}{# published #}
{% endif %}{# wordpress #}
{% endfor %}

Or for testing purposes, before passing template via pillar is working:
{% set template_name = 'artcode' %}
#{% set template_name = 'buddypress' %}
#{% set template_name = 'buddypress_artcode' %}


template-include-{{ instance }}:
include:
  - {{ template_name }} #without .sls extension
 
  
Salt state templates are in: /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/templates
For example:
artcode.sls (just artcode plugin)
buddypress.sls (just buddypress plugin)
buddypress_artcode.sls (artcode and buddypress plugin)
etc...

Plugins are in: /srv/wordpress-selfservice/plugins

How can I carry over the {{ htmldir }} variable and the requirement for cmd: selfservice-install (from wordpress.sls) into the template states?

Something like this (or not necessary because {{ htmldir }} is not defined in wordpress.sls, global?
  - defaults:
        htmldir: {{ htmldir }}

In template file:		
	
# Steve note: rather than copying the plugin dir, this could be a file-managed tarball or zip.
# However, I found that this caused problems with the source_hash, when the plugin developer makes changes, there is a risk of hash mismatches.

# ******** Start plugin

{% set plugin_name = 'artcode' %}
		
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


TO DO:
# Taxonomy in WPSS plugin needs to detect categories automatically from the dir /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/templates ??
# Handlers for what happens in Salt when Admin interface updates or deletes a site. The events are: 'Publish', 'Update' and 'Move to trash'. 
# 'Publish' is already handled. 'Update' (change template) and 'Move to trash' (which is really setting 'publish' to false, probably means: delete WP instance, including mySQL tables and apache host file). 

BUGS:

System has suddenly stopped making WP instances!

Symtoms-

The dir /srv/selfservice fails to get created (around line 55 in /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/wordpress.sls? Where 'makedirs: True' should check it's been created).

After publishing from the admin interface, there are no host created files in: /etc/apache2/sites-available

Running salt-minion in debug, I can see the salt event being generated when the 'publish' button is clicked in WP admin.
I can also see that the WP instance is unable to install because the dir /srv/selfservice doesn't exist.

Attempt to fix 1:
Replaced original master.sls (so using Chris' version of plugin: https://github.com/cgreenhalgh/wordpress-selfservice/archive/0.2.tar.gz)
Therefore, all of plugin files are original.
wpss version in /srv is Steve's, because vagrant file is using: git clone https://github.com/laplizard/wordpress-selfservice.git
Still not creating instances...
What else has changed?

Attempt to fix 2:
As above, but I switched /srv version back to Chris' version
Still not creating instances... Hmmm...
  
  
  
  


