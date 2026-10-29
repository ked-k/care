<?php

namespace App\Livewire\Shop\Storefront;

use Livewire\Attributes\Locked;

/** About / Delivery / Returns / Terms / Privacy — text written in the Shop settings. */
class PageComponent extends StorefrontComponent
{
    #[Locked]
    public string $pageKey = '';

    public const PAGES = [
        'about' => ['field' => 'about', 'title' => 'About us'],
        'delivery' => ['field' => 'delivery_info', 'title' => 'Delivery'],
        'returns' => ['field' => 'returns_policy', 'title' => 'Returns'],
        'terms' => ['field' => 'terms', 'title' => 'Terms of sale'],
        'privacy' => ['field' => 'privacy', 'title' => 'Privacy'],
    ];

    public function mount(string $storeSlug, string $page): void
    {
        $store = $this->resolveStore($storeSlug);
        abort_unless(isset(self::PAGES[$page]) && filled($store->{self::PAGES[$page]['field']}), 404);
        $this->pageKey = $page;
    }

    public function render()
    {
        $store = $this->store();
        $meta = self::PAGES[$this->pageKey];

        return $this->page('livewire.shop.storefront.page', [
            'title' => __($meta['title']),
            'body' => $store->{$meta['field']},
            'deliveryMethods' => $this->pageKey === 'delivery' ? $store->deliveryMethods()->active()->get() : collect(),
        ], __($meta['title']));
    }
}
