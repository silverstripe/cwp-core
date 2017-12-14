<?php

/**
 * General CWP configuration
 *
 * More configuration is applied in cwp/_config/config.yml for APIs that use
 * {@link Config} instead of setting statics directly.
 * NOTE: Put your custom site configuration into mysite/_config/config.yml
 * and if absolutely necessary if you can't use the yml file, mysite/_config.php instead.
 */

use SilverStripe\CMS\Model\SiteTree,
    SilverStripe\Forms\HTMLEditor\HTMLEditorConfig,
    SilverStripe\Control\Director,
    SilverStripe\Security\PasswordValidator,
    SilverStripe\Security\Member,
    SilverStripe\Core\Config\Config,
    SilverStripe\HybridSessions\HybridSession,
    CWP\Core\Search\CwpSolr,
    CWP\Core\Extension\LoginAttemptNotifications_LeftAndMain,
    SilverStripe\Admin\LeftAndMain;

// Tee logs to syslog to make sure we capture everything regardless of project-specific SS_Log configuration.
// @todo @upgrade
// SS_Log::add_writer(new \SilverStripe\Auditor\MonologSysLogWriter(), SS_Log::DEBUG, '<=');

// configure document converter.
// @todo @upgrade Where does DocumentConverterDecorator come from? Shpuld be of the form: DocumentConverterDecorator::class
if (class_exists('DocumentConverterDecorator') && defined('DOCVERT_USERNAME')) {
	DocumentImportIFrameField_Importer::set_docvert_username(DOCVERT_USERNAME);
	DocumentImportIFrameField_Importer::set_docvert_password(DOCVERT_PASSWORD);
	DocumentImportIFrameField_Importer::set_docvert_url(DOCVERT_URL);
	Page::add_extension('DocumentConverterDecorator');
}

// set the system locale to en_GB. This also means locale dropdowns
// and date formatting etc will default to this locale. Note there is no
// English (New Zealand) option
// @todo @upgrade
//i18n::set_locale('en_GB');

// default to the binary being in the usual path on Linux
if(!defined('WKHTMLTOPDF_BINARY')) {
	define('WKHTMLTOPDF_BINARY', '/usr/local/bin/wkhtmltopdf');
}

CwpSolr::configure();

// TinyMCE configuration
$cwpEditor = HTMLEditorConfig::get('cwp');

// Start with the same configuration as 'cms' config (defined in framework/admin/_config.php).
$cwpEditor->setOptions(array(
	'friendly_name' => 'Default CWP',
	'priority' => '60',
	'mode' => 'none',
	'body_class' => 'typography',
	'document_base_url' => Director::absoluteBaseURL(),
	'cleanup_callback' => "sapphiremce_cleanup",
	'use_native_selects' => false,
	'valid_elements' => "@[id|class|style|title],a[id|rel|rev|dir|tabindex|accesskey|type|name|href|target|title"
		. "|class],-strong/-b[class],-em/-i[class],-strike[class],-u[class],#p[id|dir|class|align|style],-ol[class],"
		. "-ul[class],-li[class],br,img[id|dir|longdesc|usemap|class|src|border|alt=|title|width|height|align|data*],"
		. "-sub[class],-sup[class],-blockquote[dir|class],"
		. "-table[cellspacing|cellpadding|width|height|class|align|dir|id|style],"
		. "-tr[id|dir|class|rowspan|width|height|align|valign|bgcolor|background|bordercolor|style],"
		. "tbody[id|class|style],thead[id|class|style],tfoot[id|class|style],"
		. "#td[id|dir|class|colspan|rowspan|width|height|align|valign|scope|style|headers],"
		. "-th[id|dir|class|colspan|rowspan|width|height|align|valign|scope|style|headers],caption[id|dir|class],"
		. "-div[id|dir|class|align|style],-span[class|align|style],-pre[class|align],address[class|align],"
		. "-h1[id|dir|class|align|style],-h2[id|dir|class|align|style],-h3[id|dir|class|align|style],"
		. "-h4[id|dir|class|align|style],-h5[id|dir|class|align|style],-h6[id|dir|class|align|style],hr[class],"
		. "dd[id|class|title|dir],dl[id|class|title|dir],dt[id|class|title|dir],@[id,style,class]",
	'extended_valid_elements' =>
		'img[class|src|alt|title|hspace|vspace|width|height|align|name|usemap|data*],'
		. 'object[classid|codebase|width|height|data|type],'
		. 'embed[width|height|name|flashvars|src|bgcolor|align|play|loop|quality|allowscriptaccess|type|pluginspage|autoplay],'
		. 'param[name|value],'
		. 'map[class|name|id],'
		. 'area[shape|coords|href|target|alt],'
		. 'ins[cite|datetime],del[cite|datetime],'
		. 'menu[label|type],'
		. 'meter[form|high|low|max|min|optimum|value],'
		. 'cite,abbr,,b,article,aside,code,col,colgroup,details[open],dfn,figure,figcaption,'
		. 'footer,header,kbd,mark,,nav,pre,q[cite],small,summary,time[datetime],var,ol[start|type]',
	'browser_spellcheck' => true,
	'theme_advanced_blockformats' => 'p,pre,address,h2,h3,h4,h5,h6'
));

$cwpEditor->enablePlugins('media', 'fullscreen', 'inlinepopups');
$cwpEditor->enablePlugins('template');
$cwpEditor->enablePlugins('visualchars');
$cwpEditor->enablePlugins('xhtmlxtras');
$cwpEditor->enablePlugins(array(
	'ssbuttons' => sprintf('../../../%s/tinymce_ssbuttons/editor_plugin_src.js', THIRDPARTY_DIR),
	'ssmacron' => sprintf('../../../%s/tinymce_ssmacron/editor_plugin_src.js', THIRDPARTY_DIR)
));

// First line:
$cwpEditor->insertButtonsAfter('strikethrough', 'sub', 'sup');
$cwpEditor->removeButtons('underline', 'strikethrough', 'spellchecker');

// Second line:
$cwpEditor->insertButtonsBefore('formatselect', 'styleselect');
$cwpEditor->addButtonsToLine(2,
	'ssmedia', 'sslink', 'unlink', 'anchor', 'separator','code', 'fullscreen', 'separator',
	'template', 'separator', 'ssmacron'
);
$cwpEditor->insertButtonsAfter('pasteword', 'removeformat');
$cwpEditor->insertButtonsAfter('selectall', 'visualchars');
$cwpEditor->removeButtons('visualaid');

// Third line:
$cwpEditor->removeButtons('tablecontrols');
$cwpEditor->addButtonsToLine(3, 'cite', 'abbr', 'ins', 'del', 'separator', 'tablecontrols');

// Configure password strength requirements
$pwdValidator = new PasswordValidator();
$pwdValidator->minLength(8);
$pwdValidator->checkHistoricalPasswords(6);
$pwdValidator->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"));

Member::set_password_validator($pwdValidator);

// Disable the feature. LoginAttempt seems to be broken on bridging solution - logs every request instead of logins!
if (class_exists(LoginAttemptNotifications_LeftAndMain::class)) {
	LeftAndMain::add_extension(LoginAttemptNotifications_LeftAndMain::class);
}

// Initialise the redirection configuration if null.
if (is_null(Config::inst()->get('CwpControllerExtension', 'ssl_redirection_force_domain'))) {
	if (defined('CWP_SECURE_DOMAIN')) {
		Config::inst()->update('CwpControllerExtension', 'ssl_redirection_force_domain', CWP_SECURE_DOMAIN);
	} else {
		Config::inst()->update('CwpControllerExtension', 'ssl_redirection_force_domain', false);
	}
}

// @todo session_keepalive_ping isn't set correctly if you define it in YAML. This is a workaround until the
// framework gets upgraded to a newer version in composer.
Config::inst()->update('LeftAndMain', 'session_keepalive_ping', false);

// Automatically configure session key for activedr with hybridsessions module
// @todo @upgrade Use Environment::env()
if(defined('CWP_INSTANCE_DR_TYPE') && CWP_INSTANCE_DR_TYPE === 'active'
	&& defined('SS_SESSION_KEY') && class_exists('HybridSessionStore')
) {
	HybridSession::init(SS_SESSION_KEY);
}
