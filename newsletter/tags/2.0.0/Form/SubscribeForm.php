<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Form;

use tiFy\Plugins\Newsletter\NewsletterAwareTrait;
use tiFy\Plugins\Newsletter\Contracts\SubscribeForm as SubscribeFormContract;
use tiFy\Form\FormFactory;
use tiFy\Support\DateTime;
use tiFy\Support\Proxy\Request;

class SubscribeForm extends FormFactory implements SubscribeFormContract
{
    use NewsletterAwareTrait;

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        parent::boot();

        $this->form()->events()->listen('request.prepared', [$this, 'onRequestPrepared']);
        $this->form()->events()->listen('request.validated', [$this, 'onRequestValidated']);
        $this->form()->events()->listen('request.proceed', [$this, 'onRequestProceed']);
    }

    /**
     * @inheritDoc
     */
    public function onRequestPrepared(): void
    {
        // Vérification de l'IP
        $from = DateTime::now(DateTime::getGlobalTimeZone())->subHour()->toDateTimeString();
        $ip = Request::ip();

        if ($this->newsletter()->dbSubscribers()->where(compact('ip'))->where('updated_at', '>', $from)->count() > 6) {
            $this->form()->error(__(
                'Un trop grand nombre de tentatives d\'inscriptions sont répertoriées depuis cette adresse IP' .
                ' veuillez réessayer plus tard.',
                'tify'
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function onRequestValidated(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function onRequestProceed(): void
    {
        // Enregistrement en base et expédition du mail d'optin.
        $request = $this->form()->request();
        $email = $request->get('email');

        if (!$exists = $this->newsletter()->dbSubscribers()->where('email', $email)->first()) {
            $keys = $this->newsletter()->generateOptinKeys($email);

            $this->newsletter()->dbSubscribers()->insert([
                'email'       => $email,
                'ip'          => Request::ip(),
                'private_key' => $keys['private'] ?? null,
                'created_at'  => DateTime::now(),
                'updated_at'  => DateTime::now(),
            ]);

            $this->newsletter()->optinMail($keys['public'], $email)->send();
        } else {
            if ($exists->status === 'on-hold') {
                if ($this->newsletter()->isExpiredPrivateKey($exists->private_key)) {
                    $keys = $this->newsletter()->generateOptinKeys($email);

                    $this->newsletter()->dbSubscribers()->where('id', $exists->id)->update([
                        'ip'          => Request::ip(),
                        'private_key' => $keys['private'] ?? null,
                        'updated_at'  => DateTime::now(),
                    ]);

                    $this->newsletter()->optinMail($keys['public'], $exists->email)->send();
                } else {
                    $this->form()->error(__(
                        'Cette adresse de messagerie a déjà été enregistrée mais l\'inscription n\'a pas encore été ' .
                        ' validée. Un email de confirmation a déjà été récemment acheminé. Veuillez vérifiez votre' .
                        ' client de messagerie ou réessayer plus tard.',
                        'tify'
                    ));
                }
            } else {
                $this->form()->error(__(
                    'Cette adresse de messagerie a déjà été enregistrée dans la liste des abonnés' .
                    ' à la lettre d\'informations.',
                    'tify'
                ));
            }
        }
    }
}