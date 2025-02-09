<?php

/*
 * This file is part of the PHPFlasher package.
 * (c) Younes KHOUBZA <younes.khoubza@gmail.com>
 */

namespace Flasher\Tests\Prime\Response\Presenter;

use Flasher\Prime\Notification\Envelope;
use Flasher\Prime\Notification\Notification;
use Flasher\Prime\Response\Presenter\HtmlPresenter;
use Flasher\Prime\Response\Response;
use Flasher\Tests\Prime\TestCase;

class HtmlPresenterTest extends TestCase
{
    /**
     * @return void
     */
    public function testArrayPresenter()
    {
        $envelopes = array();

        $notification = new Notification();
        $notification->setMessage('success message');
        $notification->setTitle('PHPFlasher');
        $notification->setType('success');
        $envelopes[] = new Envelope($notification);

        $notification = new Notification();
        $notification->setMessage('warning message');
        $notification->setTitle('yoeunes/toastr');
        $notification->setType('warning');
        $envelopes[] = new Envelope($notification);

        $livewireListener = $this->getLivewireListenerScript();

        $response = <<<JAVASCRIPT
<script type="text/javascript" class="flasher-js">
(function() {
    var rootScript = '';
    var FLASHER_FLASH_BAG_PLACE_HOLDER = {};
    var options = mergeOptions({"envelopes":[{"notification":{"type":"success","message":"success message","title":"PHPFlasher","options":[]}},{"notification":{"type":"warning","message":"warning message","title":"yoeunes\/toastr","options":[]}}]}, FLASHER_FLASH_BAG_PLACE_HOLDER);

    function mergeOptions(first, second) {
        return {
            context: merge(first.context || {}, second.context || {}),
            envelopes: merge(first.envelopes || [], second.envelopes || []),
            options: merge(first.options || {}, second.options || {}),
            scripts: merge(first.scripts || [], second.scripts || []),
            styles: merge(first.styles || [], second.styles || []),
        };
    }

    function merge(first, second) {
        if (Array.isArray(first) && Array.isArray(second)) {
            return first.concat(second).filter(function(item, index, array) {
                return array.indexOf(item) === index;
            });
        }

        return Object.assign({}, first, second);
    }

    function renderOptions(options) {
        if(!window.hasOwnProperty('flasher')) {
            console.error('Flasher is not loaded');
            return;
        }

        requestAnimationFrame(function () {
            window.flasher.render(options);
        });
    }

    function render(options) {
        if ('loading' !== document.readyState) {
            renderOptions(options);

            return;
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderOptions(options);
        });
    }

    if (1 === document.querySelectorAll('script.flasher-js').length) {
        document.addEventListener('flasher:render', function (event) {
            render(event.detail);
        });

        {$livewireListener}
    }

    if (window.hasOwnProperty('flasher') || !rootScript || document.querySelector('script[src="' + rootScript + '"]')) {
        render(options);
    } else {
        var tag = document.createElement('script');
        tag.setAttribute('src', rootScript);
        tag.setAttribute('type', 'text/javascript');
        tag.onload = function () {
            render(options);
        };

        document.head.appendChild(tag);
    }
})();
</script>
JAVASCRIPT;

        $presenter = new HtmlPresenter();

        $this->assertEquals($response, $presenter->render(new Response($envelopes, array())));
    }

    /**
     * @return void
     */
    public function testItRenderOnlyEnvelopesAsJsonObject()
    {
        $envelopes = array();

        $notification = new Notification();
        $notification->setMessage('success message');
        $notification->setTitle('PHPFlasher');
        $notification->setType('success');
        $envelopes[] = new Envelope($notification);

        $notification = new Notification();
        $notification->setMessage('warning message');
        $notification->setTitle('yoeunes/toastr');
        $notification->setType('warning');
        $envelopes[] = new Envelope($notification);

        $response = '{"envelopes":[{"notification":{"type":"success","message":"success message","title":"PHPFlasher","options":[]}},{"notification":{"type":"warning","message":"warning message","title":"yoeunes\/toastr","options":[]}}]}';

        $presenter = new HtmlPresenter();

        $this->assertEquals($response, $presenter->render(new Response($envelopes, array('envelopes_only' => true))));
    }

    /**
     * Generate the script for Livewire event handling.
     *
     * @return string
     */
    private function getLivewireListenerScript()
    {
        if (!class_exists('Livewire\LivewireManager')) {
            return '';
        }

        return <<<JAVASCRIPT
document.addEventListener('livewire:navigating', function () {
    var elements = document.querySelectorAll('.fl-no-cache');
    for (var i = 0; i < elements.length; i++) {
        elements[i].remove();
    }
});
JAVASCRIPT;
    }
}
