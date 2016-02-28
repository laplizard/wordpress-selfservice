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
/srv/wordpress-selfservice/saltstack/salt/selfservice/master.sls (AKA selfservice.master.sls)

2. WP instances for individuals (including a selection of plugins controlled by state file templates) are installed by the state file: /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/wordpress.sls (AKA selfservice.sites.wordpress.sls)

Salt stuff:
Master and one minion are on the same machine.
Both master.conf and minion-local-dev.conf have the same fileroots:
/srv/wordpress-selfservice/saltstack/salt-local
/srv/wordpress-selfservice/saltstack/salt

The only difference between the two conf files is that the master.conf has reactor scripts specified to handle 'selfservice/www' salt events.

At runtime Salt looks in the above fileroots for a top.sls, finding it in /srv/wordpress-selfservice/saltstack/salt-local.

The top.sls tells a minion called 'dev' to load the git formulas (loaded by the Vagrant file) for installing apache, mysql and php.

Then top.sls goes to the states:
selfservice.vhosts.standard.sls (which does some stuff about setting up Apache hosts...needs expanding)
selfservice.master.sls (which installs the main admin copy of WP in the web root, with the WPSS plugin installed)
selfservice.sites.wordpress.sls (which does nothing on first time called, because no sites in salt pillar. When all is refreshed, after a new WP instance is created, then this is the bit that installs WP and any required plugins, via the templates).

Clicking 'Publish', 'Update' or 'Move to trash'. on the WP admin installation, websites post type page (should!) update the taxonomy status for the created post. This indicates which template radio-button has been selected (if not selected, then this is set to the 'vanilla' template, with no plugins installed).

How it should work:
The plugin fires off an event (‘selfservice/www’) that is interpreted by a Salt Reactor script, eventually causing wordpress.sls to handle this as required.
If a post has the status 'publish' (and it hasn't already been setup), then a new instance of WP is installed. The post's template name is used to include salt templates, which will in turn copy the appropropriate plugins into the new WP instance (and activate them using WP CLI). 

The chain of events that happen when 'publish' is clicked:
selfservice.php (wpss_on_save_post() > selfservice.php (wpss_send_event( ) ) > fires event salt-call event.send ‘the event name is ‘selfservice/www’ with a payload of site ‘instance’ and site ‘status'. Note: payload is NOT urrently used > because master.conf includes a ‘reactor’ section specifying how  Salt should respond to 'selfservice/www' events, it triggers the running of 2 scripts > script (1): refresh_pillar.sls (causes pillar data to refresh and master.conf to reload) > wpss_get_pillar.php > wpssmanager.php (get_pillar() - which uses WPQuery to get  'wpss_site' posts and add them to the pillar data). THEN...script (2) selfservice-update.sls (which includes the Salt states selfservice.vhosts.standard and selfservice.sites.wordpress - the latter installs the new instance of WP- it goes through all ‘sites’ in the pillar data, but only adds an instance if not already present.

 
In wordpress.sls, the template selection bit looks a something like this:

{% for htmldir,site in salt['pillar.get']('selfservice:sites', {}).items() %}
{% if site.get('type','') == 'wordpress' %}
{% if site.get('status','publish') != 'publish' %}
{# not published... ?? #}
{% else %}

{% set template_name = site.get('template','') %}

{% endif %}{# published #}
{% endif %}{# wordpress #}
{% endfor %}

Or for testing purposes, before passing template name via pillar is working:
{% set template_name = 'artcode' %}
#{% set template_name = 'buddypress' %}
#{% set template_name = 'buddypress_artcode' %}

Then, a template file is included:

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
# Taxonomy in WPSS plugin needs to detect 'terms' (template names) automatically from the dir /srv/wordpress-selfservice/saltstack/salt/selfservice/sites/templates ?? UPDATE: this is difficult security wise, because don't want the plugin files to access the file system outside the web root... hmmm...
# Handlers for what happens in Salt when Admin interface updates or deletes a site. The events are: 'Publish' (, 'Update' and 'Move to trash'. 'Publish' is already handled. 'Update' (change template) and 'Move to trash' (which is on the Salt pillar as status=deleted, means: delete WP instance, including mySQL tables and apache host file). 
# Admin interface - after clicking 'Publish' on a website, user should see a link to the new site (otherwise, how do they know the address!).

BUGS:

# System has suddenly stopped making WP instances!

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
  
 # (when instance creation was still working!), over 100 x instances of WP caused a sudden slowdown. After creation, the host file and htmldir didn't appear for up to 15 minutes! 
 
Vagrant/VirtualBox running out of space? 
This was the used space at >100 instances:
Disk space: 
                     size     used    available
vagrant         368G  347G   22G  95% /vagrant
  
 # Salt not finding Salt config files, on 'vagrant up'. This was caused by Git having failed to install. Therefore, WPSS git file was never installed. Also, this meant that directory didn’t exist to copy Salt config file too. FIXED! SOLUTION: Git was failing because the wrong version of Git was being looked for. Putting ‘apt-get update’ in just before the Git installation (in Vagrant File) fixed this. 

# When a post of custom type 'wpss_site' is put in the trash, it's name is still reserved. So, if a site called 'bob' is trashed and then a new site called 'bob' is created, the address for the new WP instance is: http://127.0.0.1:8080/bob-2/, rather than http://127.0.0.1:8080/bob/
I guess this would be fixed by "after clicking 'Publish' on a website, user should see a link to the new site" in TO DO above.
 
 
 