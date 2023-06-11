<?php

declare(strict_types=1);

namespace Flasher\Prime\Response\Presenter;

use Flasher\Prime\Response\Response;

final class HtmlPresenter implements PresenterInterface
{
    public const FLASHER_FLASH_BAG_PLACE_HOLDER = '/** FLASHER_FLASH_BAG_PLACE_HOLDER **/';

    public const HEAD_END_PLACE_HOLDER = '</head>';

    public const BODY_END_PLACE_HOLDER = '</body>';

    public function render(Response $response): string
    {
        $jsonOptions = json_encode($response->toArray()) ?: '';
        $context = $response->getContext();

        if (isset($context['envelopes_only']) && true === $context['envelopes_only']) {
            return $jsonOptions;
        }

        $mainScript = $response->getRootScript();
        $placeholder = self::FLASHER_FLASH_BAG_PLACE_HOLDER;

        return $this->renderJavascript($jsonOptions, $mainScript, $placeholder);
    }

    private function renderJavascript(string $jsonOptions, string $mainScript, string $placeholder): string
    {
        return <<<JAVASCRIPT
<script type="text/javascript" class="flasher-js">
(function(global, document) {
    const merge = (first, second) => {
        if (Array.isArray(first) && Array.isArray(second)) {
            return [...first, ...second.filter(item => !first.includes(item))];
        }

        if (typeof first === 'object' && typeof second === 'object') {
            for (const [key, value] of Object.entries(second)) {
                first[key] = first.hasOwnProperty(key) ? { ...first[key], ...value } : value;
            }
            return first;
        }

        return undefined;
    };

    const mergeOptions = (...options) => {
        const result = {};

        options.forEach(option => {
            Object.entries(option).forEach(([key, value]) => {
                result[key] = result.hasOwnProperty(key) ? merge(result[key], value) : value;
            });
        });

        return result;
    };

    const renderOptions = options => {
        if(!global.hasOwnProperty('flasher')) {
            console.error('Flasher is not loaded');
            return;
        }

        requestAnimationFrame(function () {
            global.flasher.render(options);
        });
    }

    const render = options => {
        const readyState = document.readyState;
        if (readyState === 'interactive' || readyState === 'complete') {
            renderOptions(options);
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                renderOptions(options);
            });
        }
    }

    const mainScript = '{$mainScript}';
    const optionsRegistry = [];
    optionsRegistry.push({$jsonOptions});
    {$placeholder}
    const options = mergeOptions(...optionsRegistry);
    console.log(options);

    if (document.querySelector('script.flasher-js')) {
        document.addEventListener('flasher:render', e => render(e.detail));
    }

    if (global.hasOwnProperty('flasher') || !mainScript || document.querySelector('script[src="' + mainScript + '"]')) {
        render(options);
    } else {
        const tag = document.createElement('script');
        tag.src = mainScript;
        tag.type = 'text/javascript';
        tag.onload = () => render(options);

        document.head.appendChild(tag);
    }
})(window, document);
</script>
JAVASCRIPT;
    }
}
