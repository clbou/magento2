<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Css\PreProcessor\Adapter;

use Magento\Framework\App\State;
use Pelago\Emogrifier\CssInliner as EmogrifierCssInliner;
use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * This class will inline the css of an html to each tag to be used for applications such as a styled email.
 */
class CssInliner
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @param State $appState
     */
    public function __construct(State $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @param string $html
     * @param string $css
     * @return EmogrifierCssInliner
     * @throws ParseException
     */
    public function setHtmlCss(string $html, string $css = '')
    {
        return EmogrifierCssInliner::fromHtml($html)->disableStyleBlocksParsing()
            ->inlineCss($css)
            ->setDebug($this->appState->getMode() === State::MODE_DEVELOPER);
    }
}
