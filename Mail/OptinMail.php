<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Mail;

use tiFy\Mail\Mail as BaseMail;
use tiFy\Plugins\Newsletter\{
    Contracts\OptinMail as OptinMailContract,
    NewsletterAwareTrait
};

class OptinMail extends BaseMail implements OptinMailContract
{
    use NewsletterAwareTrait;

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return array_merge(parent::defaults(), [
            'subject' => sprintf(
                __('[%s] >> Validation d\'inscription à la lettre d\'informations', 'tify'), get_bloginfo('blogname')),
            'viewer'  => [
                'override_dir' => $this->newsletter()->resources('/views/mail/optin'),
            ],
        ]);
    }
}
