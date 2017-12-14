<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension,
    SilverStripe\View\Requirements,
    SilverStripe\Forms\Form;

class CustomHtmlEditorFieldToolbar extends Extension
{

    /**
     * @param Form $form
     * @return void
     */
    public function updateMediaForm(Form $form)
    {
        Requirements::add_i18n_javascript('cwp-core/javascript/lang');
        Requirements::javascript('cwp-core/javascript/CustomHtmlEditorFieldToolbar.js');
    }

}
