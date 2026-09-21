<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\View\Requirements;

/**
 * Adds the CWP JavaScript to the CMS insert media form.
 *
 * Not applied anywhere: `HtmlEditorField_Toolbar` does not exist in CMS 6.
 *
 * @extends Extension<\HtmlEditorField_Toolbar>
 */
class CustomHtmlEditorFieldToolbar extends Extension
{
    /**
     * @param Form $form
     * @return void
     */
    public function updateMediaForm(Form $form)
    {
        Requirements::add_i18n_javascript('cwp/cwp-core:javascript/lang');
        Requirements::javascript('cwp/cwp-core:javascript/CustomHtmlEditorFieldToolbar.js');
    }
}
