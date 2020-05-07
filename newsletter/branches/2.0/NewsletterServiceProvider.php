<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use tiFy\Container\ServiceProvider;

class NewsletterServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        add_action('after_setup_theme', function () {
            $this->getContainer()->share('newsletter', new Newsletter($this->getContainer()->get('app')));
        });
    }
}