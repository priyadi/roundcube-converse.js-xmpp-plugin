Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js. This is very
much a work in progress.

Requirements
------------
* BOSH support in XMPP server or BOSH connection manager
* (optional) BOSH proxy in web server, to avoid crossdomain issues
* (recommended) XMPP server set to broadcast incoming messages to all resources. See notes below.

Installation
------------
* `cd your_roundcube_dir/plugins`
* `git clone https://github.com/priyadi/roundcube-converse.js-xmpp-plugin converse`
* `cd converse`
* `cp config.inc.php.dist config.inc.php`
* `vi config.inc.php` (make necessary adjustments)
* `cd your_roundcube_dir/`
* `vi config/main.inc.php` (add 'converse' to $rcmail_config['plugins'])
* done!

Notes
-----

This plugin creates a new XMPP session on each page rendering in order to
support multiple active window. To avoid confusion it is recommended to have
the XMPP server broadcast incoming messages to all resources.

* Openfire: "route.all-resources: true"
* Prosody: "ignore_presence_priority = true"

Troubleshooting
---------------

Read [troubleshooting](TROUBLESHOOTING.md) if you are having problem. 

Development
-----------
The plugin has a complete and minified version of the converse.js library with
all its dependencies included. For development, you can pull converse.js as
a git submodule and include the scripts and style sheets directly from there.

1. Load the converse.js and xmpp-prebind-php submodule:
   ```
   cd cd your_roundcube_dir/plugins/converse
   git submodule init && git submodule update
   ```

2. Set `$rcmail_config['converse_xmpp_devel_mode'] = true;` in this plugins
   config file.

Credits
-------
* Some code were stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* [Candy Chat](http://candy-chat.github.io/candy/) for its prebinding library
