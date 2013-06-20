Roundcube Converse.js XMPP plugin 
=================================

This is XMPP plugin for Roundcube Webmail based on converse.js. This is very
much a work in progress.

Requirements
------------
* An XMPP server with the same credentials as IMAP server used by Roundcube. This also means the IMAP server must accept full email address as login username.
* BOSH support in XMPP server or BOSH connection manager
* Plaintext auth in BOSH
* (optional) BOSH proxy in web server, to avoid crossdomain issues
* (recommended) XMPP server set to broadcast incoming messages to all resources ("route.all-resources: true" in Openfire, "ignore_presence_priority = true" in Prosody)

Currently the BOSH endpoint is hardcoded at '/http-bind'. This will be made
configurable later.

Installation
------------
* cd your_roundcube_dir/plugins
* git clone https://github.com/priyadi/roundcube-converse.js-xmpp-plugin converse
* cd converse
* git submodule init && git submodule update
* cd your_roundcube_dir/
* vi config/main.inc.php (add 'converse' to $rcmail_config['plugins'])
* done!

Credits
-------
* Some code is stolen from https://gist.github.com/Zash/3681653
* [Converse.js](http://conversejs.org)
* This work is sponsored by indoglobal.com
