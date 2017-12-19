<?php

/**
 * General CWP configuration
 *
 * More configuration is applied in cwp/_config/config.yml for APIs that use
 * {@link Config} instead of setting statics directly.
 * NOTE: Put your custom site configuration into mysite/_config/config.yml
 * and if absolutely necessary if you can't use the yml file, mysite/_config.php instead.
 */

use CWP\Core\Extension\LoginAttemptNotifications;
use CWP\Core\Search\CwpSolr;
use CWP\Core\Extension\CwpControllerExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\HybridSessions\HybridSession;
use SilverStripe\i18n\i18n;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

// set the system locale to en_GB. This also means locale dropdowns
// and date formatting etc will default to this locale. Note there is no
// English (New Zealand) option
i18n::set_locale('en_GB');

// default to the binary being in the usual path on Linux
if (!Environment::getEnv('WKHTMLTOPDF_BINARY')) {
    Environment::setEnv('WKHTMLTOPDF_BINARY', '/usr/local/bin/wkhtmltopdf');
}

CwpSolr::configure();

// TinyMCE configuration
/** @var TinyMCEConfig $cwpEditor */
$cwpEditor = HTMLEditorConfig::get('cwp');

// Start with the same configuration as 'cms' config (defined in framework/admin/_config.php).
$cwpEditor->setOptions([
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
        . 'embed[width|height|name|flashvars|src|bgcolor|align|play|loop|quality|'
        . 'allowscriptaccess|type|pluginspage|autoplay],'
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
]);

$cwpEditor->enablePlugins('media', 'fullscreen', 'inlinepopups');
$cwpEditor->enablePlugins('template');
$cwpEditor->enablePlugins('visualchars');
$cwpEditor->enablePlugins('xhtmlxtras');
$cwpEditor->enablePlugins([
    'ssbuttons' => sprintf('../../../%s/tinymce_ssbuttons/editor_plugin_src.js', THIRDPARTY_DIR),
    'ssmacron' => sprintf('../../../%s/tinymce_ssmacron/editor_plugin_src.js', THIRDPARTY_DIR)
]);

// First line:
$cwpEditor->insertButtonsAfter('strikethrough', 'sub', 'sup');
$cwpEditor->removeButtons('underline', 'strikethrough', 'spellchecker');

// Second line:
$cwpEditor->insertButtonsBefore('formatselect', 'styleselect');
$cwpEditor->addButtonsToLine(
    2,
    'ssmedia',
    'sslink',
    'unlink',
    'anchor',
    'separator',
    'code',
    'fullscreen',
    'separator',
    'template',
    'separator',
    'ssmacron'
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
$pwdValidator->characterStrength(3, ["lowercase", "uppercase", "digits", "punctuation"]);

Member::set_password_validator($pwdValidator);

// Initialise the redirection configuration if null.
// @todo nesting the config manifests here is not ideal. Work out a way that doesn't need to do that.
if (is_null(Config::inst()->get(CwpControllerExtension::class, 'ssl_redirection_force_domain'))) {
    if (Environment::getEnv('CWP_SECURE_DOMAIN')) {
        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_force_domain', CWP_SECURE_DOMAIN);
    } else {
        Config::modify()->set(CwpControllerExtension::class, 'ssl_redirection_force_domain', false);
    }
}

// Automatically configure session key for activedr with hybridsessions module
if (Environment::getEnv('CWP_INSTANCE_DR_TYPE')
    && Environment::getEnv('CWP_INSTANCE_DR_TYPE') === 'active'
    && Environment::getEnv('SS_SESSION_KEY')
    && class_exists(HybridSession::class)
) {
    HybridSession::init(Environment::getEnv('SS_SESSION_KEY'));
}
