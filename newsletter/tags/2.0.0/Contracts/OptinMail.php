<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Contracts;

use tiFy\Contracts\Mail\Mail;
use tiFy\Plugins\Newsletter\NewsletterAwareTrait;

/**
 * @mixin NewsletterAwareTrait
 */
interface OptinMail extends Mail
{

}