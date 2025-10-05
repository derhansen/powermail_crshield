<?php

declare(strict_types=1);

namespace Derhansen\PowermailCrshield\Utility;

use In2code\Powermail\Domain\Model\Form;

class FormSaltUtility
{
    public static function getFormSalt(Form $form): string
    {
        return 'powermail-form-' . $form->getUid() . '-' . count($form->getPages());
    }
}
