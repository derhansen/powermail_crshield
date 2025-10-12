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
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\Page\PageInformation;

class CrFieldViewHelper extends AbstractFormFieldViewHelper
{
    private const FIELDNAME = 'field[crfield]';

    protected $tagName = 'input';
    private int $currentTimestamp;
    private int $cacheTimeOutDefault = 86400;
    private array $settings;

    public function __construct(
        private readonly ChallengeResponseService $challengeResponseService,
        private readonly Context $context
    ) {
        $this->currentTimestamp = $this->context->getPropertyFromAspect('date', 'timestamp');
        $this->settings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('powermail_crshield');
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

        $challenge = $this->challengeResponseService->getChallenge(
            (string)($this->settings['obfuscationMethod'] ?? '1'),
            $this->getPageExpirationTime(),
            (int)($this->settings['crJavaScriptDelay'] ?? 3),
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

    protected function getPageExpirationTime(): int
    {
        $pageRecord = $this->getPageRecord();
        if ($pageRecord === []) {
            return 0;
        }

        $timeOutTime = $this->getCacheTimeout();
        if ($timeOutTime < (int)($this->settings['minimumPageExpirationTime'] ?? 900)) {
            $timeOutTime += (int)($this->settings['additionalPageExpirationTime'] ?? 3600);
        }

        return $timeOutTime + $this->currentTimestamp;
    }

    /**
     * Get the cache timeout for the current page (taken 1:1 from TypoScriptFrontendController)
     */
    protected function getCacheTimeout(): int
    {
        $pageInformation = $this->getRequest()->getAttribute('frontend.page.information');
        $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
        return GeneralUtility::makeInstance(CacheLifetimeCalculator::class)
            ->calculateLifetimeForPage(
                $pageInformation->getId(),
                $pageInformation->getPageRecord(),
                $typoScriptConfigArray,
                $this->cacheTimeOutDefault,
                $this->context
            );
    }

    protected function getPageRecord(): array
    {
        /** @var PageInformation $pageInformation */
        $pageInformation = $this->getRequest()->getAttribute('frontend.page.information');
        return $pageInformation->getPageRecord();
    }
}
