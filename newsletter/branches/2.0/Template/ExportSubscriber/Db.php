<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Template\ExportSubscriber;

use tiFy\Database\Model;
use tiFy\Template\Factory\FactoryAwareTrait;
use tiFy\Contracts\Template\FactoryDb as DbContract;
use tiFy\Plugins\Newsletter\NewsletterAwareTrait;

class Db extends Model implements DbContract
{
    use FactoryAwareTrait, NewsletterAwareTrait;

    /**
     * @var string
     */
    protected $table = 'tify_newsletter_subscribers';

    /**
     * @inheritDoc
     */
    public function newQuery()
    {
        return parent::newQuery()->where('status', 'subscriber');
    }
    /**/
}