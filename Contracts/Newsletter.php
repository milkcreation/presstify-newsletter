<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Contracts;

use InvalidArgumentException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Psr\Container\ContainerInterface as Container;
use tiFy\Contracts\{Routing\Route, Support\ParamsBag};
use tiFy\Plugins\Newsletter\Exception\ExpiredException;

interface Newsletter
{
    /**
     * Récupération de l'instance de l'extension gestion des inforamtions de contact.
     *
     * @return static|null
     *
     * @throws \Exception
     */
    public static function instance(): ?Newsletter;

    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): Newsletter;

    /**
     * Récupération de paramètre|Définition de paramètres|Instance du gestionnaire de paramètre.
     *
     * @param string|array|null $key Clé d'indice du paramètre à récupérer|Liste des paramètre à définir.
     * @param mixed $default Valeur de retour par défaut lorsque la clé d'indice est une chaine de caractère.
     *
     * @return mixed|ParamsBag
     */
    public function config($key = null, $default = null);

    /**
     * Instance d'une requête de traitement des abonnés en base de données.
     *
     * @return QueryBuilder
     */
    public function dbSubscribers(): QueryBuilder;

    /**
     * Géneration de la clé d'optin.
     *
     * @param string $email
     *
     * @return array
     */
    public function generateOptinKeys(string $email): array;

    /**
     * Récupération du conteneur d'injection de dépendances.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container;

    /**
     * Vérifie l'expiration de la clé de contrôle de validation.
     *
     * @param string $private
     *
     * @return bool
     */
    public function isExpiredPrivateKey(string $private): bool;

    /**
     * Instance de l'email de validation d'abonnement.
     *
     * @param string $public_key Clé publique de validation.
     * @param string $to Email de destination
     * @param array $params Liste des paramètres complémentaires.
     *
     * @return OptinMail
     */
    public function optinMail(string $public_key, string $to, array $params = []): OptinMail;

    /**
     * Chemin absolu vers une ressources (fichier|répertoire).
     *
     * @param string $path Chemin relatif vers la ressource.
     *
     * @return string
     */
    public function resources(string $path = ''): string;

    /**
     * Récupération de l'instance d'une route déclarée.
     *
     * @param string $name verify-optin|...
     *
     * @return Route|null
     */
    public function route(string $name): ?Route;

    /**
     * Définition des paramètres de configuration.
     *
     * @param array $attrs Liste des attributs de configuration.
     *
     * @return static
     */
    public function setConfig(array $attrs): Newsletter;

    /**
     * Définition du conteneur d'injection de dépendances.
     *
     * @param Container $container
     *
     * @return static
     */
    public function setContainer(Container $container): Newsletter;

    /**
     * Instance du formulaire associé.
     *
     * @return SubscribeForm|null
     */
    public function subscribeForm(): ?SubscribeForm;

    /**
     * Vérification de la clé d'optin.
     *
     * @param string $public
     * @param string $private
     *
     * @return bool
     *
     * @throws InvalidArgumentException|ExpiredException
     */
    public function verifyOptin(string $public, string $private): bool;
}
