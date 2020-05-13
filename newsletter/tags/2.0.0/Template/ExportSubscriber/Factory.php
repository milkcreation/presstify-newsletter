<?php declare(strict_types=1);

namespace tiFy\Plugins\Newsletter\Template\ExportSubscriber;

use tiFy\Plugins\Newsletter\NewsletterAwareTrait;
use tiFy\Plugins\Transaction\Wordpress\Template\ExportListTableWpBase\Factory as BaseFactory;

class Factory extends BaseFactory
{
    use NewsletterAwareTrait;

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        $this->set([
            'labels'    => [
                'plural'     => __('Export des abonnés', 'tify'),
                'singular'   => __('Export des abonnés', 'tify'),
                'page_title' => __('Export des abonnés', 'tify'),
            ],
            'params'    => [
                'ajax'         => false,
                'columns'      => [
                    'id',
                    'email'
                ],
                /*'query_args'   => [
                    'order'    => 'DESC',
                    'per_page' => 20,
                ],*/
                'bulk-actions' => false,
                'row-actions'  => false,
                'search'       => false,
                'view-filters' => false,
                'table'        => [
                    'before' => '<div class="TableResponsive">',
                    'after'  => '</div>',
                ],
                'wordpress'    => [
                    'admin_menu' => [
                        'parent_slug' => 'newsletter',
                        'position'    => 0,
                    ],
                ],
            ],
            'providers' => [
                'db'   => (new Db())->setNewsletter($this->newsletter)
            ],
        ]);
    }
}