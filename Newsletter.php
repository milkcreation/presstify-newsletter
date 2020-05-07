<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use Psr\Container\ContainerInterface as Container;
use tiFy\Plugins\Newsletter\{
    Contracts\Newsletter as NewsletterContract,
};

/**
 * @desc Extension PresstiFy de gestion de newsletter.
 * @author Jordy Manner <jordy@milkcreation.fr>
 * @package tiFy\Plugins\Newsletter
 * @version 2.0.0
 *
 * USAGE :
 * Activation
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans config/app.php
 * >> ajouter NewsletterServiceProvider à la liste des fournisseurs de services.
 * <?php
 *
 * return [
 *      ...
 *      'providers' => [
 *          ...
 *          tiFy\Plugins\Newsletter\NewsletterServiceProvider::class
 *          ...
 *      ]
 * ];
 *
 * Configuration
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans le dossier de config, créer le fichier newsletter.php
 * @see /vendor/presstify-plugins/newsletter/Resources/config/newsletter.php
 */
class Newsletter implements NewsletterContract
{
    /**
     * Instance de l'extension de gestion des information de contact.
     * @var Newsletter|null
     */
    protected static $instance;

    /**
     * Instance du gestionnaire d'injection de dépendances.
     * @var Container
     */
    protected $container;

    /**
     * CONSTRUCTEUR.
     *
     * @param Container $container
     *
     * @return void
     */
    public function __construct(?Container $container = null)
    {
        if (!static::$instance instanceof NewsletterContract) {
            static::$instance = $this;

            if (!is_null($container)) {
                $this->setContainer($container);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function instance(): ?NewsletterContract
    {
        return static::$instance;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function resources($path = ''): string
    {
        $path = $path ? '/' . ltrim($path, '/') : '';

        return file_exists(__DIR__ . "/Resources{$path}") ? __DIR__ . "/Resources{$path}" : '';
    }

    /**
     * @inheritDoc
     */
    public function setContainer(Container $container): NewsletterContract
    {
        $this->container = $container;

        return $this;
    }
}