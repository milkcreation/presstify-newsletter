<?php declare(strict_types=1);

use tiFy\Plugins\Newsletter\{
    Contracts\Newsletter as NewsletterContract,
    Newsletter
};

if (!function_exists('newsletter')) {
    function newsletter(): ?NewsletterContract
    {
        try {
            return Newsletter::instance();
        } catch (Exception $e) {
            return null;
        }
    }
}