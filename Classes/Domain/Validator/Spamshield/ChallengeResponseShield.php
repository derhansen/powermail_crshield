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
}
