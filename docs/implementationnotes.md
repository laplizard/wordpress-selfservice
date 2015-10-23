# Implementation Notes

## Overview / Walkthrough

The vagrantfile sets up a dev/test machine (based on Ubuntu) with salt 
installed (master and minioin).

Salt configures the dev machine with apache, mysql, PHP, and an 
wordpress site under URL path `selfservice`. This includes the 
selfservice plugin (from the plugins directory), which defines a custom
post type (`wpss_site`) which represents a requested website and provides
the user interface. This can be accessed
via [http://127.0.0.1:8080/selfservice](http://127.0.0.1:8080/selfservice)
(default username `admin`, password `SPs8vm2p`).

When a site is created/updated in WP selfservice the plugin fires a 
salt event `selfservice/www`.
The salt master configuration (`/etc/salt/master` from `saltstack/etc/master.conf`) causes the master to execute the reactor scripts `saltstack/reactor/refresh_pillar.sls` and `.../selfservice-update.sls`

The `refresh_pillar.sls` script causes salt to refresh (i.e. reload) its pillar state; the master is configured (same file) to include the output of `scripts/wpss_get_pillar.php` in its pillar state. This script outputs data about the current `wpss_site` custom types formatted as needed by the salt scripts, specifically some information to set up an apache vhost config and some information to set up a separate wordpress installation (files and database) - see details below. This must be run on the selfservice WP installation machine (need to be `root` or `www-data`). 

The `selfservice-update` script does the equivalent of `salt '*' state.sls selfservice.update`, i.e. apply state in `saltstack/salt/selfservice/update.sls` which in turn includes the states `selfservice.vhosts.standard` and `selfservice.sites.wordpress`. State `selfservice.vhosts.standard` is a variant of the standard apache formula for setting up virtual hosts, but expanded to also allow paths within a vhost to map to different directories – currently each new site is a path, name=WP slug wpss  post, mapped to the directory where it will be installed – currently all on 127.0.0.1 (for dev). All parameters from wpss via the pillar as above.

Salt state `selfservice.sites.wordpress` iterates over all requested wordpress sites (using the pillar state obtained above), and installs wordpress, sets up the database and does other configuration. One day similar scripts would use docker, etc. to set up other kinds of services.

Once that has finished you should be able to access the new site as `http://127.0.0.1:8080/newsite` (or whatever name you gave it). If you update the title, description or slug in selfservice WP then it should all happen again and the site get updated.

## Data Exported From Wordpress Plugin to SaltStack Configuration

The wordpress plugin sets up the selfservice instance with a custom post
type wpss_site, each instance of which represents target state for one
website.

This configuration is extracted and filtered to create (external) pillar
state. 

One part configures apache. Pillar `apache:sites:` and within that 
`locations`. Processed by SLS `selfservice.vhost.standard`.
E.g.
```
apache:
  sites:
    127.0.0.1:
      locations:
        /WEBPATH:
          id: ID
          DocumentRoot: DIRECTORY
          available: true
          defaultMessage: ... [todo]
```
Another part specifies WordPress initialisation. Pillar `selfservice:sites:`.
e.g.
```
selfservice: [todo]
  sites:
    DIRECTORY:
      id: ID
      type: wordpress
      # wordpress-specific...
      url: http://SERVER:PORT/WEBPATH
      admin_password_hash: PASSWORDHASH
      title: TITLE
      description: DESCRIPTION
      status: publish|trash|auto-draft|...      
```

TODO: minion selection/filtering!

Pillar data is exported from selfservice server with:
```
sudo -u www-data /srv/wordpress-selfservice/scripts/wpss_get_pillar.php --path=/var/www/html/selfservice
```

Apache (www-data) can send event back to salt (permission in sudoers) with:
```
sudo /usr/bin/salt-call event.send selfservice/www param=value...
```

## Limitations

Currently...

-	All working on only one hosting machine; will need to generalise at some point to have multiple minions each running a specific subset of sites
-	No feedback to end-user of state of configuration/update; maybe should use a salt beacon
-	Just sets up a basic vanilla WP site; need to add templates for different site set-ups (e.g. sets of plugins w configurations) which end-user can choose and set-up can make
-	No backups or other general maintainance
-	Want other useful features like temporary sites which are automatically tidied up afterwards
-	No checks on site name collisions
-	No way to specify real vhosts or external site names
