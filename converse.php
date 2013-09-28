<?php

/**
 * Converse.js based XMPP chat plugin plugin for Roundcube webmail
 *
 * @author Priyadi Iman Nurcahyo
 * @author Thomas Bruederli <thomas@roundcube.net>
 *
 * Copyright (C) 2013, Priyadi Iman Nurcahyo http://priyadi.net
 * Copyright (C) 2013, The Roundcube Dev Team <hello@roundcube.net>
 *
 * This software is published under the MIT license.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 */

class converse extends rcube_plugin
{
	public $task = '?(?!logout).*';
	public $noframe = true;
	public $noajax = true;
	private $debug = false;
	private $devel_mode = false;

	function init() {
		$this->load_config();

		// we at least require a BOSH url in config
		if ($this->_config_get('converse_xmpp_bosh_url')) {
		    $this->add_texts('localization/', false);
			$this->add_hook('render_page', array($this, 'render_page'));
			$this->add_hook('authenticate', array($this, 'authenticate'));
			$this->debug = $this->_config_get('converse_xmpp_debug', false);
			$this->devel_mode = $this->_config_get('converse_xmpp_devel_mode', false);
		}
	}

	function render_page($event) {
		$rcmail = rcube::get_instance();

		// TODO: exclude some more actions
		if ($rcmail->task == 'login' || !empty($_REQUEST['_extwin']))
			return;

		// map session language with converse.js locale
		$locale = 'en';
		$userlang = $rcmail->get_user_language();
		$userlang_ = substr($userlang, 0, 2);
		$locales = array('en','de','fr','es','it','af','pt_BR');
		if (in_array($userlang, $locales))
			$locale = $userlang;
		else if (in_array($userlang_, $locales))
			$locale = $userlang_;

		$converse_prop = array(
			'animate' => true,
			'prebind' => false,
			'xhr_user_search' => false,
			'auto_subscribe' => false,
			'auto_list_rooms' => true,
			'hide_muc_server' => true,
			'show_controlbox_by_default' => false,
			'debug' => $this->debug,
		);

		// prebind
		if (!empty($_SESSION['converse_xmpp_prebind'])) {
			$args = $_SESSION['converse_xmpp_prebind'];
			$xsess = new XmppPrebindSession($args['bosh_prebind_url'], $args['host'], $args['user'], $rcmail->decrypt($args['pass']));
			$xsess->debug = $this->debug;
			if ($xsess->init_connection() && $xsess->bind()){
				$converse_prop['prebind'] = true;
				$converse_prop['bosh_service_url'] = $args['bosh_url'];
				$converse_prop['jid'] = $xsess->jid;
				$converse_prop['sid'] = $xsess->sid;
				$converse_prop['rid'] = $xsess->rid;
			}
			else {
				$rcmail->session->remove('xmpp');
			}
		}
		else if ($this->_config_get('converse_xmpp_enable_always')) {
			$converse_prop['bosh_service_url'] = $this->_config_get('converse_xmpp_bosh_url', array(), '/http-bind');
		}
		else {
			return;
		}

		if ($this->devel_mode) {
			$this->include_script('converse.js/components/requirejs/require.js');
			$this->include_script('js/main.js');
			$this->include_stylesheet('converse.js/converse.css');
		}
		else {
			$this->include_script('js/converse.min.js');
			$this->include_stylesheet('css/converse.min.css');
		}

		$this->include_stylesheet('converse.css');

		$this->api->output->add_footer(
			html::div(array('id' => "chatpanel"),
				html::div(array('id' => "collective-xmpp-chat-data"), '').
				html::div(array('id' => "toggle-controlbox"),
					html::a(array('href' => '#', 'class' => "chat toggle-online-users"),
						html::tag('strong', 'conn-feedback', $this->gettext('togglechat')).
						html::tag('strong', array('style' => "display:none", 'id' => "online-count"), '(0)')
					)
				)
			)
		);

		$this->api->output->add_script('
	define("jquery", [], function() { return jQuery; });
	require.config({ baseUrl: "'.$this->urlbase.'converse.js" });
	require(["converse"], function (converse) {
		var args = '.$rcmail->output->json_serialize($converse_prop).';
		args.i18n = locales["'.$locale.'"];
		converse.initialize(args, function(e){ console.log(converse) });
	});
	', 'foot');
	}

	function authenticate($args) {
		if ($prebind_url = $this->_config_get('converse_xmpp_bosh_prebind_url', $args)) {
			$rcmail = rcmail::get_instance();
			$_SESSION['converse_xmpp_prebind'] = array(
				'bosh_prebind_url' => $prebind_url,
				'bosh_url' => $this->_config_get('converse_xmpp_bosh_url', $args, '/http-bind'),
				'host' => $this->_config_get('converse_xmpp_hostname', $args, $args['host']),
				'user' => $this->_config_get('converse_xmpp_username', $args, $args['user']),
				'pass' => $rcmail->encrypt($this->_config_get('converse_xmpp_password', $args, $args['pass'])),
			);
		}

		return $args;
	}

	function _config_get($opt, $args = array(), $default = null) {
		$rcmail = rcmail::get_instance();
		$value = $rcmail->config->get($opt, $default);
		if (is_callable($value))
			return $value($args);
		return $value;
	}
}

/**
 * Helper class to perform BOSH XMPP pre-binding
 */
class XmppPrebindSession
{
	public $rid;
	public $jid;
	public $sid;
	public $debug = false;
	private $hostname;
	private $username;
	private $password;
	private $resource;
	private $bosh_url;

	function __construct($bosh_url, $hostname, $username, $password) {
		$this->bosh_url = $bosh_url;
		$this->hostname = $hostname;
		$this->password = $password;
		$this->parse_jid($username);
		$this->rid = rand(0x1000, 0xfffffff);
	}

	private function parse_jid($username) {
		if (strpos($username, '/')) {
			list($username, $this->resource) = explode('/', $username);
		}

		if (strpos($username, '@')) {
			list($this->username, $this->hostname) = explode('@', $username);
		}
		else {
			$this->username = $username;
		}
	}

	function get_request_xml_init($wait = 60, $hold = 1) {
		$xml = simplexml_load_string('<body xmlns="http://jabber.org/protocol/httpbind" xml:lang="en" content="text/xml; charset=utf-8" ver="1.6" xmpp:version="1.0" xmlns:xmpp="urn:xmpp:xbosh"/>');
		$xml->addAttribute('wait', $wait);
		$xml->addAttribute('hold', $hold);
		return $xml;
	}

	function get_request_xml_auth() {
		$bosh_xml = simplexml_load_string('<body xmlns="http://jabber.org/protocol/httpbind">'.
			'<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN"></auth>'.
		'</body>');
		$bosh_xml->auth = $this->get_auth_blob();
		return $bosh_xml;
	}

	function get_auth_blob() {
		return base64_encode("\0{$this->username}@{$this->hostname}\0{$this->password}");
	}

	function get_request_xml_restart() {
		return '<body xmlns="http://jabber.org/protocol/httpbind" xml:lang="en" xmpp:restart="true" xmlns:xmpp="urn:xmpp:xbosh"/>';
	}

	function get_request_xml_bind() {
		$bosh_xml = simplexml_load_string('<body xmlns="http://jabber.org/protocol/httpbind">'.
			'<iq xmlns="jabber:client" type="set" id="_bind_auth_2">'.
				'<bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"/>'.
			'</iq>'.
		'</body>');
		if ($this->resource)
			$bosh_xml->iq->bind->resource = $this->resource;
		return $bosh_xml;
	}

	function get_request_xml_session() {
		return '<body xmlns="http://jabber.org/protocol/httpbind">'.
			'<iq type="set" id="_session_auth_2" xmlns="jabber:client">'.
				'<session xmlns="urn:ietf:params:xml:ns:xmpp-session"/>'.
			'</iq>'.
		'</body>';
	}

	function request_body($xml) {
		$bosh_xml = is_string($xml) ? simplexml_load_string($xml) : $xml;
		$bosh_xml->addAttribute('rid', $this->rid++);
		$bosh_xml->addAttribute('to', $this->hostname);
		if ($this->sid)
			$bosh_xml->addAttribute('sid', $this->sid);
		return $bosh_xml->asXML();
	}

	function send_request($body) {
		$bosh_xml = $this->request_body($body);
		if ($this->debug)
			rcube::write_log('xmpp', "C: " . $bosh_xml);

		if($ch = curl_init()) {
			curl_setopt_array($ch, array(
				CURLOPT_URL => $this->bosh_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $bosh_xml,
				CURLOPT_HTTPHEADER => array('Content-Type: application/xml'),
			));
			$out = curl_exec($ch);
			if ($this->debug)
				rcube::write_log('xmpp', "S: " . ($out ?: '<no-response>'));

			if ($err = curl_error($ch)) {
				rcube::raise_error("Converse-XMPP: HTTP connection error: " . $err);
			}

			return strlen($out) ? simplexml_load_string($out) : null;
		}
	}

	function init_connection(){
		if ($init_result = $this->send_request($this->get_request_xml_init())) {
			$this->sid = (string)$init_result->attributes()->sid[0];

			if ($auth_result = $this->send_request($this->get_request_xml_auth())) {
				if (isset($auth_result->success)) {
					// restart connection
					$xml_result = $this->send_request($this->get_request_xml_restart());
					return isset($xml_result) && !isset($xml_result->error);
				}
			}
		}

		rcube::raise_error("Converse-XMPP: Login failed for ". $this->username, true);
		return false;
	}

	function bind() {
		if($bind_result = $this->send_request($this->get_request_xml_bind())) {
			if (isset($bind_result->iq->bind->jid[0])){
				$this->jid = (string)$bind_result->iq->bind->jid[0];
				$sess_result = $this->send_request($this->get_request_xml_session());
				return true;
			}
		}

		rcube::raise_error("Converse-XMPP: No JID returned for " . $this->username, true);
		return false;
	}
}
