<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use Exception;
use tiFy\Contracts\Http\Response;
use tiFy\Routing\BaseController;
use tiFy\Support\DateTime;
use tiFy\Support\Proxy\{Partial, Request};

class NewsletterController extends BaseController
{
    use NewsletterAwareTrait;

    /**
     * Validation d'un abonnement à la lettre d'informations.
     *
     * @param string $public_key
     *
     * @return Response
     */
    public function verifyOptin(string $public_key): Response
    {
        $email = Request::input('email', '');

        if (!$user = $this->newsletter()->dbSubscribers()->where(compact('email'))->first()) {
            $this->set('notice', Partial::get('notice', [
                'content' => __('Impossible de retrouver l\'inscription en correspondance avec cet email.', 'tify'),
                'type'    => 'error',
            ]));
        } elseif ($user->status !== 'on-hold') {
            $this->set('notice', Partial::get('notice', [
                'content' => __('Votre inscription à la lettre d\'informations a déjà été validée.', 'tify'),
                'type'    => 'info',
            ]));
        } else {
            try {
                $this->newsletter()->verifyOptin($public_key, $user->private_key);

                $this->newsletter()->dbSubscribers()->where('id', $user->id)->update([
                    'private_key' => '',
                    'status'      => 'subscriber',
                    'updated_at'  => DateTime::now(),
                ]);

                $this->set('notice', Partial::get('notice', [
                    'content' => __('Félicitation votre inscription à la lettre d\'informations a été validée.',
                        'tify'),
                    'type'    => 'success',
                ]));
            } catch (Exception $e) {
                $this->set('notice', Partial::get('notice', [
                    'content' => __('Impossible de valider votre inscription à la lettre d\'informations.', 'tify'),
                    'type'    => 'error',
                ]));
            }
        }

        return $this->viewVerifyOptin();
    }

    /**
     * Affichage de la validation d'un abonnement à la lettre d'informations.
     *
     * @return Response
     */
    public function viewVerifyOptin(): Response
    {
        return $this->view('newsletter::app/verify-optin', $this->all());
    }
}