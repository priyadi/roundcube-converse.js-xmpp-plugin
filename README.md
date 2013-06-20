Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js. This is very
much a work in progress.

Requirements
------------
* BOSH support in XMPP server or BOSH connection manager
* Plaintext auth in BOSH. This currently doesn't support other auth methods
* (optional) BOSH proxy in web server, to avoid crossdomain issues
* (recommended) XMPP server set to broadcast incoming messages to all resources. See notes below.

Installation
------------
* cd your_roundcube_dir/plugins
* git clone https://github.com/priyadi/roundcube-converse.js-xmpp-plugin converse
* cd converse
* git submodule init && git submodule update
* vi config.inc.php (make necessary adjustments)
* cd your_roundcube_dir/
* vi config/main.inc.php (add 'converse' to $rcmail_config['plugins'])
* done!

Notes
-----

This plugin creates a new XMPP session on each page rendering in order to
support multiple active window. To avoid confusion it is recommended to have
the XMPP server broadcast incoming messages to all resources.

* Openfire: "route.all-resources: true"
* Prosody: "ignore_presence_priority = true"

Credits
-------
* Some code is stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* This work is sponsored by [indoglobal.com](http://indoglobal.com)
