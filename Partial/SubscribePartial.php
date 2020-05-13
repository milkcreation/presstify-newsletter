<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Partial;

use tiFy\Plugins\Newsletter\NewsletterAwareTrait;
use tiFy\Partial\PartialDriver;

class SubscribePartial extends PartialDriver
{
    use NewsletterAwareTrait;

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return $this->newsletter()->subscribeForm()->render();
    }
}
