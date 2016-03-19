
/**
 * Converse XMPP client integration script for Roundcube webmail
 */
function rcmail_converse_init(converse, args)
{
    // get last RID from local storage
    if (args.sid && !args.rid && window.localStorage) {
        args.rid = rcmail.local_storage_get_item('converse.rid');
    }

    converse.initialize(args, function(e){ /* console.log(converse) */ });

    // hook into login event and keep XMPP session in Roundcube's session
    converse.listen.on('onReady', function(e){
        if (!args.sid && e.target.bare_jid)
            rcmail.http_post('plugin.converse_bind', { jid:e.target.bare_jid, sid:converse.tokens.get('sid') });
    });

    // store last RID for continuing session after page reload
    $(window).bind('unload', function(){
        if (window.localStorage && window.rcmail && converse.tokens.get('sid')) {
            rcmail.local_storage_set_item('converse.rid', converse.tokens.get('rid'));
        }
    });
}



