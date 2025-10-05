<?php

declare(strict_types=1);

namespace Derhansen\PowermailCrshield\ViewHelpers;

use Derhansen\PowermailCrshield\Service\ChallengeResponseService;
use Derhansen\PowermailCrshield\Utility\FormSaltUtility;
use In2code\Powermail\Domain\Model\Form;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CrFieldViewHelper extends AbstractFormFieldViewHelper
{
    private const FIELDNAME = 'field[crfield]';

    protected $tagName = 'input';

    public function __construct(
        private readonly ChallengeResponseService $challengeResponseService,
        private readonly Context $context
    ) {
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('form', 'object', 'The Powermail form object', true);
    }

    public function render(): string
    {
        $this->registerFieldNameForFormTokenGeneration(self::FIELDNAME);
        $this->setRespectSubmittedDataValue(true);

        /** @var Form $form */
        $form = $this->arguments['form'];
        $salt = FormSaltUtility::getFormSalt($form);

        $extensionSettings = $this->getExtensionSettings();
        $challenge = $this->challengeResponseService->getChallenge(
            (string)($extensionSettings['obfuscationMethod'] ?? '1'),
            $this->getPageExpirationTime(),
            (int)($extensionSettings['crJavaScriptDelay'] ?? 3),
            $salt
        );

        if ($this->isConfirmationPage()) {
            $value = $this->getSubmittedValue();
        } else {
            $value = base64_encode($challenge);
        }

        $this->tag->addAttribute('id', 'powermail-' . $form->getUid() . '-cr-field');
        $this->tag->addAttribute('type', 'hidden');
        $this->tag->addAttribute('name', $this->prefixFieldName(self::FIELDNAME));
        $this->tag->addAttribute('value', $value);
        $this->tag->addAttribute('autocomplete', 'off');

        $this->addAdditionalIdentityPropertiesIfNeeded();

        return $this->tag->render();
    }

    private function getSubmittedValue(): string
    {
        return $this->getRequest()->getParsedBody()['tx_powermail_pi1']['field']['crfield'] ?? '';
    }

    private function isConfirmationPage(): bool
    {
        /** @var Request|null $extbaseRequest */
        $extbaseRequest = $this->getRequest()->getAttribute('extbase');
        return $this->getRequest()->getMethod() === 'POST' && $extbaseRequest && $extbaseRequest->getControllerExtensionName() === 'Powermail' &&
            $extbaseRequest->getControllerActionName() === 'confirmation';
    }
    
    private function getPageExpirationTime(): int
    {
        $currentTimestamp = $this->context->getPropertyFromAspect('date', 'timestamp');

        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $this->getRequest()->getAttribute('frontend.controller') ?? $GLOBALS['TSFE'];
        // TSFE to not contains a valid page record?!
        if (!$tsfe || !is_array($tsfe->page)) {
            return 0;
        }
        $timeOutTime = $tsfe->get_cache_timeout();

        // If page has a endtime before the current timeOutTime, use it instead:
        if ($tsfe->page['endtime']) {
            $endtimePage = (int)($tsfe->page['endtime']) - $currentTimestamp;
            if ($endtimePage && $endtimePage < $timeOutTime) {
                $timeOutTime = $endtimePage;
            }
        }

        $extensionSettings = $this->getExtensionSettings();
        if ($timeOutTime < (int)($extensionSettings['minimumPageExpirationTime'] ?? 900)) {
            $timeOutTime += (int)($extensionSettings['additionalPageExpirationTime'] ?? 3600);
        }

        return $timeOutTime + $currentTimestamp;
    }

    private function getExtensionSettings(): array
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('powermail_crshield');
    }
}
