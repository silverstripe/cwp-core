<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\View\Requirements;

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
