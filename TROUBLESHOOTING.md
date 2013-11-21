Troubleshooting
===============

First, make sure your BOSH endpoint is working correctly
--------------------------------------------------------

Launch [Pidgin](http://pidgin.im), add an account and set it up to connect to
your BOSH endpoint. In Advanced tab, specify your BOSH URL there. Use the URL
that will be used by Roundcube to connect. In most cases this URL will have the
same hostname as your Roundcube installation.

If that does not work, you need to work on that first. See log files of your
XMPP server and your proxying web server. Consult the available documentation
and support channels of your XMPP server and/or web server.

Logging BOSH connection
-----------------------

Launch Chrome, hit F12 (developer tools), go to Network tab, select XHR (on
the bottom). BOSH connections are ones that have path like 'http-bind' (or
whatever your BOSH endpoint is).

Clicking on it will reveal detailed information on the right pane. What
interesting to us are the request (Headers tab, section 'Request Payload') and
the response (the Response tab). Make sure you provide us with these
information on bug reports.

