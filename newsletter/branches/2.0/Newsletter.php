<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use Exception;
use InvalidArgumentException;
use Illuminate\Database\{Schema\Blueprint, Query\Builder as QueryBuilder};
use Psr\Container\ContainerInterface as Container;
use Ramsey\Uuid\{Uuid, UuidInterface};
use tiFy\Contracts\Routing\Route;
use tiFy\Plugins\Newsletter\{Contracts\Newsletter as NewsletterContract,
    Contracts\OptinMail as OptinMailContract,
    Contracts\SubscribeForm as SubscribeFormContract,
    Exception\ExpiredException,
    Form\SubscribeForm,
    Mail\OptinMail,
    Partial\SubscribePartial,
    Template\ExportSubscriber\Factory as ExportSubscriberTermplate
};
use tiFy\Support\{ParamsBag, Str};
use tiFy\Support\Proxy\{Database, Form, Partial, Schema, Router, Template, View};
use PasswordHash;

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
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Indicateur d'initialisation.
     * @var UuidInterface|null
     */
    protected $uuid;

    /**
     * Instance du gestionnaire d'injection de dépendances.
     * @var Container|null
     */
    protected $container;

    /**
     * Instance de la configuration associée.
     * @var ParamsBag|null
     */
    protected $config;

    /**
     * Instance du formulaire d'abonnement.
     * @var SubscribeFormContract|null
     */
    protected $subscribeForm;

    /**
     * Instance de la configuration associée.
     * @var Route[]|array
     */
    protected $route = [];

    /**
     * CONSTRUCTEUR.
     *
     * @param array $config
     * @param Container $container
     *
     * @return void
     */
    public function __construct(array $config = [], ?Container $container = null)
    {
        $this->setConfig($config);

        if (!is_null($container)) {
            $this->setContainer($container);
        }

        if (!self::$instance instanceof NewsletterContract) {
            self::$instance = $this;
        }
    }

    /**
     * @inheritDoc
     */
    public static function instance(): ?NewsletterContract
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        throw new Exception(__('Impossible de récupérer l\'instance du gestionnaire de newsletter.', 'tify'));
    }

    /**
     * @inheritDoc
     */
    public function boot(): NewsletterContract
    {
        if (!$this->booted) {
            /* UUID */
            $this->uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'tiFyNewsletterPlugin');
            /**/

            /* MENU D'ADMINISTRATION */
            // -- Déclaration des entrées de menu
            add_action('admin_menu', function () {
                $m = $this->config('admin_menu', []);

                add_menu_page(
                    $m['page_title'] ?? __('Newsletter', 'tify'),
                    $m['menu_title'] ?? __('Newsletter', 'tify'),
                    $m['capability'] ?? 'edit_posts',
                    $m['menu_slug'] ?? 'newsletter',
                    $m['function'] ?? '__return_false',
                    $m['icon_url'] ?? 'dashicons-email',
                    $m['position'] ?? 99
                );
            });

            /* Désactivation de l'entrée de sous menu principale */
            add_filter('submenu_file', function ($submenu_file) {
                $slug = $this->config('admin_menu.menu_slug', 'newsletter');
                remove_submenu_page($slug, $slug);

                return $submenu_file;
            });
            /**/

            /* BASE DE DONNEES */
            if (is_admin()) {
                Database::addConnection(
                    array_merge(Database::getConnection()->getConfig(), ['strict' => false]), 'tify.newsletter'
                );
                $schema = Schema::connexion('tify.newsletter');

                // -- Table d'enregistrement des abonnés.
                if (!$schema->hasTable('tify_newsletter_subscribers')) {
                    $schema->create('tify_newsletter_subscribers', function (Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('email', 255);
                        $table->string('ip', 32);
                        $table->string('private_key', 255)->nullable();
                        $table->string('status', 25)->default('on-hold');
                        $table->longText('data')->nullable();
                        $table->timestamps();
                        $table->index('email', 'email');
                    });
                }
            }
            /**/

            /* FORMULAIRES */
            $factory = $this->config()->pull('form.subscribe.factory');
            if (is_string($factory) && class_exists($factory)) {
                $factory = new $factory;
            }

            if (!$factory instanceof SubscribeFormContract) {
                $factory = new SubscribeForm();
            }

            Form::set('newsletter-form', $factory->set(array_merge([
                'auto'    => true,
                'method'  => 'GET',
                'fields'  => [
                    'email' => [
                        'attrs'       => [
                            'placeholder' => __('Saisissez votre adresse de messagerie', 'tify'),
                        ],
                        'title'       => __('Adresse de messagerie', 'theme'),
                        'required'    => true,
                        'type'        => 'text',
                        'validations' => 'email',
                    ],
                ],
                'notices' => [
                    'success' => [
                        'message' => __(
                            'Votre inscription a été enregistrée. Un email de confirmation va vous être acheminé' .
                            ' afin de valider votre inscription à la lettre d\'informations.',
                            'tify'
                        ),
                    ]
                ],
            ], $this->config('form', [])))->setNewsletter($this));

            $this->subscribeForm = Form::get('newsletter-form');
            /**/

            /* PORTIONS D'AFFICHAGE */
            Partial::register('newsletter-subscribe', (new SubscribePartial())->setNewsletter($this));
            /**/

            /* ROUTAGE */
            $controller = (new NewsletterController())->setNewsletter($this);
            $endpoints = [
                'verify-optin' => "{$this->uuid}/verify-optin/{token}"
            ];

            foreach($endpoints as $name => $endpoint) {
                $this->route[$name] = Router::get($endpoint, [$controller, Str::camel('verify-optin')]);
            }
            /**/

            /* VUES */
            View::addFolder('newsletter', $this->resources('/views'));
            /**/

            /* TEMPLATES */
            Template::set([
                'newsletter-export-subscriber' => (new ExportSubscriberTermplate())->setNewsletter($this)
            ]);
            /**/

            $this->booted = true;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function config($key = null, $default = null)
    {
        if (is_null($this->config)) {
            $this->config = new ParamsBag();
        }

        if (is_string($key)) {
            return $this->config->get($key, $default);
        } elseif (is_array($key)) {
            return $this->config->set($key);
        } else {
            return $this->config;
        }
    }

    /**
     * @inheritDoc
     */
    public function dbSubscribers(): QueryBuilder
    {
        return Database::table('tify_newsletter_subscribers');
    }

    /**
     * @inheritDoc
     */
    public function generateOptinKeys(string $email): array
    {
        global $wp_hasher;

        $public = (string)wp_generate_password(20, false);

        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }

        return [
            'public'  => $public,
            'private' => time() . ':' . $wp_hasher->HashPassword($public)
        ];
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
    public function isExpiredPrivateKey(string $private): bool
    {
        if (false !== strpos($private, ':')) {
            [$expiration] = explode(':', $private, 2);
            $expire = $expiration + DAY_IN_SECONDS;
        } else {
            return true;
        }

        $now = time();

        if ($now > $expire) {
            return true;
        } elseif (($expiration + 600) - $now < 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function optinMail(string $public_key, string $to, array $params = []): OptinMailContract
    {
        $verify_optin_url = $this->route('verify-optin')->getUrl(['token' => $public_key, 'email' => $to], true);

        return (new OptinMail())->setNewsletter($this)->setParams(
            array_merge($params, compact('to')))->data(compact('verify_optin_url'));
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
    public function route(string $name): ?Route
    {
        return $this->route[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setConfig(array $attrs): NewsletterContract
    {
        $this->config($attrs);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(Container $container): NewsletterContract
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function subscribeForm(): ?SubscribeFormContract
    {
        return $this->subscribeForm;
    }

    /**
     * @inheritDoc
     */
    public function verifyOptin(string $public, string $private): bool
    {
        global $wp_hasher;

        $key = preg_replace('/[^a-z0-9]/i', '', $public);

        if (empty($key) || !is_string($key)) {
            throw new InvalidArgumentException('invalid public key format');
        }

        if (false !== strpos($private, ':')) {
            [$expiration, $pass_key] = explode(':', $private, 2);
            $expire = $expiration + DAY_IN_SECONDS;
        } else {
            throw new InvalidArgumentException('invalid private key format');
        }

        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }

        if (time() > $expire) {
            throw new ExpiredException('expired private key');
        } elseif ($wp_hasher->CheckPassword($key, $pass_key)) {
            return true;
        }

        return false;
    }
}