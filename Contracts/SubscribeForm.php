<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Contracts;

use tiFy\Contracts\Form\FormFactory;
use tiFy\Plugins\Newsletter\NewsletterAwareTrait;

/**
 * @mixin NewsletterAwareTrait
 */
interface SubscribeForm extends FormFactory
{
    /**
     * Actions lancées au démarrage de la requête de traitement du formulaire.
     * {@internal }
     * @return void
     */
    public function onRequestPrepared(): void;


    /**
     * Actions lancées à l'issue des vérification de la requête de traitement du formulaire.
     * {@internal Permet d'effectuer les vérifications complémentaires.}
     *
     * @return void
     */
    public function onRequestValidated(): void;

    /**
     * Actions lancées lors de l'exécution la requête de traitement du formulaire.
     * {@internal Enregistrement en base de données et expédition du mail d'optin.}
     *
     * @return void
     */
    public function onRequestProceed(): void;
}
