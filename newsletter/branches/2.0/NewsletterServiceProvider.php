<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use tiFy\Container\ServiceProvider;

class NewsletterServiceProvider extends ServiceProvider
{
    /**
     * Liste des noms de qualification des services fournis.
     * {@internal Permet le chargement différé des services qualifié.}
     * @var string[]
     */
    protected $provides = [
        'newsletter'
    ];

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        add_action('after_setup_theme', function () {
            $this->getContainer()->get('newsletter')->boot();
        });
    }

    /**
     *
     */
    public function register()
    {
        $this->getContainer()->share('newsletter', function () {
            return  new Newsletter(config('newsletter', []), $this->getContainer()->get('app'));
        });
    }
}