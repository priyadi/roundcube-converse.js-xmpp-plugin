<?php

class XmppPrebindSession {
	public $rid;
	public $jid;
	public $sid;
	private $hostname;
	private $username;
	private $password;
	private $bosh;

	function __construct($bosh, $hostname, $username, $password) {
		if(($atpos = strpos($username, '@')) !== FALSE)
			list($username, $hostname) = preg_split('/@/', $username);
		$this->bosh = $bosh;
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->rid = rand(0x1000, 0xfffffff);
	}

	function get_auth_blob() {
		return base64_encode("\0{$this->username}\0{$this->password}");
	}

	function get_request_xml() {
		$bosh_xml = simplexml_load_string('<?xml version="1.0"?>'.
		'<body xmlns="http://jabber.org/protocol/httpbind" '.
				'xmlns:xmpp="urn:xmpp:xbosh" xml:lang="en" wait="60" hold="1" '.
				'content="text/xml; charset=utf-8" ver="1.6" xmpp:version="1.0">'.
			'<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN"/>'.
			'<iq xmlns="jabber:client" type="set" id="bind">'.
				'<bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"/>'.
			'</iq>'.
			// '<presence xmlns="jabber:client"/>'.
		'</body>');
		$bosh_xml->addAttribute('rid', $this->rid);
		$bosh_xml->addAttribute('to', $this->hostname);
		$bosh_xml->auth = $this->get_auth_blob();
		return $bosh_xml->asXML();
	}

	function send_request($bosh_path) {
		$bosh_xml = $this->get_request_xml();
		if($ch = curl_init()) {
			curl_setopt_array($ch, array(
				CURLOPT_URL => $bosh_path,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $bosh_xml,
			));
			$out = curl_exec($ch);
			return $out;
		}
	}

	function fetch_ids() {
		if($bosh_xml_result = $this->send_request($this->bosh)) {
			if($xml_result = simplexml_load_string($bosh_xml_result)) {
				$this->jid = (string)$xml_result->iq->bind->jid[0];
				$this->sid = (string)$xml_result->attributes()->sid[0];
				$this->rid++;
				return true;
			}
		}
	}
}

class converse extends rcube_plugin {
	function init() {
		$this->load_config();
		$this->add_hook('render_page', array($this, 'render_page'));
		$this->add_hook('authenticate', array($this, 'authenticate'));
	}

	function render_page($event) {
		$rcmail = rcube::get_instance();
		switch($rcmail->task) {
			case 'mail':
			case 'addressbook':
			case 'settings':
				break;
			default:
				return;
		}
		if (isset($_GET['_framed']) || isset($_POST['_framed'])) {
			return;
		}
		if (!isset($_SESSION['xmpp'])) {
			return;
		}
		$args = $_SESSION['xmpp'];
		$xsess = new XmppPrebindSession($args['bosh_prebind_url'], $args['host'], $args['user'], $args['pass']);
		if(!$xsess->fetch_ids()) {
			unset($_SESSION['xmpp']);
			return;
		}
		$rcmail = rcmail::get_instance();
		$this->include_script('converse.js/Libraries/strophe.js');
		$this->include_script('converse.js/Libraries/strophe.roster.js');
		$this->include_script('converse.js/Libraries/strophe.muc.js');
		$this->include_script('converse.js/Libraries/strophe.vcard.js');
		$this->include_script('converse.js/Libraries/strophe.disco.js');
		$this->include_script('converse.js/Libraries/underscore.js');
		$this->include_script('converse.js/Libraries/backbone.js');
		$this->include_script('converse.js/Libraries/backbone.localStorage.js');
		$this->include_script('converse.js/Libraries/sjcl.js');
		$this->include_script('converse.js/Libraries/jquery.tinysort.js');
		$this->include_script('converse.js/converse.js');

		$this->include_stylesheet('converse.js/converse.0.3.min.css');

		$this->api->output->add_footer('
			<div id="chatpanel">
				<div id="collective-xmpp-chat-data"></div>
				<div id="toggle-controlbox">
					<a href="#" class="chat toggle-online-users">
						<strong class="conn-feedback">Toggle chat</strong> <strong style="display: none" id="online-count">(0)</strong>
					</a>
				</div>
			</div>
		');

		$rcmail->output->add_script('
				converse.initialize({
					animate: true,
					prebind: true,
					xhr_user_search: false,
					auto_subscribe: false,
					auto_list_rooms: false,
					hide_muc_server: false,
				});
				$("#chatpanel").ready(function () { 
					var connection = new Strophe.Connection('.json_serialize($args['bosh_url']).');
					connection.attach(
						'.json_serialize($xsess->jid).',
						'.json_serialize($xsess->sid).',
						'.json_serialize($xsess->rid).'
						,
						function (status) {
							if ((status === Strophe.Status.ATTACHED)
								|| (status === Strophe.Status.CONNECTED))
							{
								converse.onConnected(connection);
							} else {
								// TODO: print error message to roundcube here
								$("$chatpanel").remove();
							}
						}
					);
				})
		', 'foot');
	}

	function authenticate($args) {
		$rcmail = rcmail::get_instance();
		$func_bosh_prebind_url = $rcmail->config->get('converse_xmpp_bosh_prebind_url');
		$func_bosh_url = $rcmail->config->get('converse_xmpp_bosh_url');
		$func_hostname = $rcmail->config->get('converse_xmpp_hostname');
		$func_username = $rcmail->config->get('converse_xmpp_username');
		$func_password = $rcmail->config->get('converse_xmpp_password');
		$xmppargs = array(
			'bosh_prebind_url' => $func_bosh_prebind_url($args),
			'bosh_url' => $func_bosh_url($args),
			'host' => $func_hostname($args),
			'user' => $func_username($args),
			'pass' => $func_password($args),
		);
		$_SESSION['xmpp'] = $xmppargs;
		return $args;
	}
}

