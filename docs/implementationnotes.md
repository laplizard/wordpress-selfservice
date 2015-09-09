# Implementation Notes

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


