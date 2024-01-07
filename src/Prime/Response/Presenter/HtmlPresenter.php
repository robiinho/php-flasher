<?php

declare(strict_types=1);

namespace Flasher\Prime\Response\Presenter;

use Flasher\Prime\Response\Response;

final class HtmlPresenter implements PresenterInterface
{
    public const FLASHER_FLASH_BAG_PLACE_HOLDER = '/** FLASHER_FLASH_BAG_PLACE_HOLDER **/';
    public const HEAD_END_PLACE_HOLDER = '</head>';
    public const BODY_END_PLACE_HOLDER = '</body>';

    /**
     * @throws \JsonException
     */
    public function render(Response $response): string
    {
        $options = json_encode($response->toArray(), \JSON_THROW_ON_ERROR);
        $context = $response->getContext();

        if ($context['envelopes_only'] ?? false) {
            return $options;
        }

        $mainScript = $response->getMainScript();
        $placeholder = self::FLASHER_FLASH_BAG_PLACE_HOLDER;

        return $this->renderJavascript($options, $mainScript, $placeholder);
    }

    private function renderJavascript(string $options, string $mainScript, string $placeholder): string
    {
        return <<<JAVASCRIPT
<script type="text/javascript" class="flasher-js">
(function(window, document) {
    const merge = (first, second) => {
        if (Array.isArray(first) && Array.isArray(second)) {
            return [...first, ...second.filter(item => !first.includes(item))];
        }

        if (typeof first === 'object' && typeof second === 'object') {
            for (const [key, value] of Object.entries(second)) {
                first[key] = key in first ? { ...first[key], ...value } : value;
            }
            return first;
        }

        return undefined;
    };

    const mergeOptions = (...options) => {
        const result = {};

        options.forEach(option => {
            Object.entries(option).forEach(([key, value]) => {
                result[key] = key in result ? merge(result[key], value) : value;
            });
        });

        return result;
    };

    const renderCallback = (options) => {
        if(!window.flash) {
            throw new Error('Flasher is not loaded');
        }

        window.flash.render(options);
    };

    const render = (options) => {
        if (options instanceof Event) {
            options = options.detail;
        }

        if (['interactive', 'complete'].includes(document.readyState)) {
            renderCallback(options);
        } else {
            document.addEventListener('DOMContentLoaded', () => renderCallback(options));
        }
    };

    const addScriptAndRender = (options) => {
        const mainScript = '{$mainScript}';

        if (window.flash || !mainScript || document.querySelector('script[src="' + mainScript + '"]')) {
            render(options);
        } else {
            const tag = document.createElement('script');
            tag.src = mainScript;
            tag.type = 'text/javascript';
            tag.onload = () => render(options);

            document.head.appendChild(tag);
        }
    };

    const addRenderListener = () => {
        if (1 === document.querySelectorAll('script.flasher-js').length) {
            document.addEventListener('flasher:render', render);
        }
    };

    const options = [];
    options.push({$options});
    {$placeholder}
    addScriptAndRender(mergeOptions(...options));
    addRenderListener();
})(window, document);
</script>
JAVASCRIPT;
    }
}
