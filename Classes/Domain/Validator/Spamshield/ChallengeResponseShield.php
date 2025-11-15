<?php

declare(strict_types=1);

namespace Derhansen\PowermailCrshield\Domain\Validator\Spamshield;

use Derhansen\PowermailCrshield\Service\ChallengeResponseService;
use Derhansen\PowermailCrshield\Utility\FormSaltUtility;
use In2code\Powermail\Domain\Validator\SpamShield\AbstractMethod;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChallengeResponseShield extends AbstractMethod implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function spamCheck(): bool
    {
        if ($this->isOptinConfirmation()) {
            $this->logger->debug('Forwarded response from optin confirmation. Skipping spam check.');
            return false;
        }

        $challengeResponseService = GeneralUtility::makeInstance(ChallengeResponseService::class);
        $this->logger->debug(
            'Submitted data',
            ['field' => $this->arguments['field'] ?? [], 'mail' => $this->arguments['mail'] ?? []]
        );

        $form = $this->mail->getForm();
        $salt = FormSaltUtility::getFormSalt($form);
        $crFieldValue = $this->arguments['field']['crfield'] ?? '';
        return $challengeResponseService->isValidResponse($crFieldValue, $salt) === false;
    }

    /**
     * A successful Powermail double optin results in a forward response to the createAction. This results in
     * calling all validations again, which is unfortunate, because they all already have been executed during
     * the creation of the initial email. This function checks, if we have a valid optin confirmation and returns
     * true, if so.
     */
    private function isOptinConfirmation(): bool
    {
        if (isset($this->arguments['hash']) &&
            ($this->arguments['action'] ?? '') === 'optinConfirm' &&
            (int)($this->arguments['mail'] ?? 0) === $this->mail->getUid() &&
            $this->mail->getHidden() === false
        ) {
            return true;
        }

        return false;
    }
}
