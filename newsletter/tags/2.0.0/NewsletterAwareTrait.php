<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use tiFy\Plugins\Newsletter\Contracts\Newsletter;

trait NewsletterAwareTrait
{
    /**
     * Instance du gestionnaire de newsletter.
     * @var Newsletter|null
     */
    protected $newsletter;

    /**
     * Récupération de l'instance du gestionnaire de newsletter.
     *
     * @return Newsletter|null
     */
    public function newsletter(): ?Newsletter
    {
        return $this->newsletter;
    }

    /**
     * Définition du gestionnaire de newsletter.
     *
     * @param Newsletter $newsletter
     *
     * @return static
     */
    public function setNewsletter(Newsletter $newsletter): self
    {
        $this->newsletter = $newsletter;

        return $this;
    }
}
