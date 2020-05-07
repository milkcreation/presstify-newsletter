<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter;

use Exception;
use tiFy\Contracts\{Form\FactoryField, Form\FormFactory, Http\Response};
use tiFy\Routing\BaseController;
use tiFy\Support\Proxy\{Form, Mail, Request};
use tiFy\Validation\Validator as v;
use PasswordHash;

class NewsletterController extends BaseController
{
    /**
     * Vérification de la clé d'optin.
     *
     * @param string $key
     * @param string $email
     *
     * @return bool
     *
     * @throws Exception
     * @see check_password_reset_key
     */
    private function _checkOptinKey(string $key, string $email): bool
    {
        $key = preg_replace('/[^a-z0-9]/i', '', $key);

        if (empty($key) || !is_string($key)) {
            throw new Exception('invalid_key > wrong key format');
        }

        if (empty($login) || !is_string($login)) {
            throw new Exception('invalid_key > wrong login format');
        }

        /**
         * vérification de l'email en base
        if (!$record = QueryUser::createFromLogin($email)) {
            throw new Exception('invalid_key > invalid email');
        }
         */

        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }

        if (!$stored = $record->optin_key) {
            throw new Exception('invalid_key > missing stored_key');
        }

        if (false !== strpos($stored, ':')) {
            [$expiration, $pass_key] = explode(':', $stored, 2);
            $expire = $expiration + DAY_IN_SECONDS;
        } else {
            throw new Exception('invalid_key > wrong stored_key format');
        }

        if (!$checked = $wp_hasher->CheckPassword($key, $pass_key)) {
            throw new Exception('invalid_key > invalid pass_key');
        } elseif (time() > $expire) {
            throw new Exception('invalid_key > key expired');
        } else {
            //delete_user_meta($user->getId(), '_validation_optin_key', $stored);
            return true;
        }
    }

    /**
     * Géneration de la clé d'optin.
     *
     * @param string $email
     *
     * @return string|null
     * @see get_password_reset_key
     *
     */
    private function _getOptinKey(string $email): ?string
    {
        global $wp_hasher;

        $key = (string)wp_generate_password(20, false);

        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }

        $hashed = time() . ':' . $wp_hasher->HashPassword($key);

        return '';//update_user_meta($user->ID, '_validation_optin_key', $hashed) ? $key : null;
    }

    /**
     * Affichage des pages de mon compte.
     *
     * @return Response
     */
    public function handle(): Response
    {
        $form = Form::get('newsletter-form')->prepare();

        if (Request::isMethod('post')) {
            return $this->validateForm($form);
        } else {
            $this->set(compact('form'));

            return $this->view('app::contact/index', $this->all());
        }
    }

    /**
     * Validation de formulaire
     *
     * @param FormFactory $form
     *
     * @return Response
     */
    public function validateForm(FormFactory $form): Response
    {
        $form->request()->prepare();

        /** @var FactoryField[] $fields */
        $fields = $form->fields()->all();

        if (!$form->request()->verify()) {
            $form->error(__('Une erreur est survenue, impossible de valider votre demande de contact.', 'theme'));
        } else {
            if (!v::notEmpty()->validate($form->request()->get('email'))) {
                $fields['email']->addError(__('Veuillez renseigner votre adresse de messagerie.', 'theme'));
            } elseif (!v::email()->validate($form->request()->get('email'))) {
                $fields['email']->addError(
                    __('L\'adresse de messagerie renseignée n\'est pas un e-mail valide.', 'theme')
                );
            }
        }

        // Translation des données de requête vers le formulaire.
        foreach ($fields as $slug => $field) {
            if ($field->supports('transport')) {
                $field->setValue($form->request()->get($slug));
            }
        }

        if (!$form->hasError()) {
            Mail::create([
                'subject'  => sprintf(
                    __('[%s] >> Vérification d\'inscription à la lettre d\'informations', 'theme'), get_bloginfo('name')
                ),
                'to'       => $fields['email']->getValue(),
                'viewer'   => [
                    'override_dir' => get_template_directory() . '/views/mail/contact',
                ],
            ])->send();

            return $this->redirect($form->request()->getRedirectUrl());
        }

        $this->set(compact('form'));

        return $this->view('app::contact/index', $this->all());
    }
}