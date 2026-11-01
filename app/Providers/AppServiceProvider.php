<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Batch 43b: links to public shop pages use the shop's own web address
        // when it has one (see App\Routing\ShopAwareUrlGenerator).
        $this->app->extend('url', fn ($url) => $url instanceof \Illuminate\Routing\UrlGenerator
            ? \App\Routing\ShopAwareUrlGenerator::from($url) : $url);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Uploaded files (product photos, avatars, certificates...) get their
        // URL from this disk setting, which is built from APP_URL. When that
        // differs from the host the site is actually served on, every upload
        // saves fine but displays as a broken image — so in a web request,
        // follow the current host, the same way asset() already does.
        if (! $this->app->runningInConsole()) {
            config(['filesystems.disks.public.url' => asset('storage')]);
        }

        Livewire::component('care-timeline.care-call-timeline', \App\Livewire\CareTimeline\CareCallTimelineComponent::class);

        // Batch 17: wellbeing-observation and handover were nested on both
        // the carer's shift-visit screen and the standalone client-profile
        // page (see shift-visit.blade.php and client-profile.blade.php)
        // using the same short <livewire:x.y> tag style as
        // care-timeline.care-call-timeline above — but, unlike that one,
        // never got the matching explicit registration. Livewire's default
        // tag resolution maps a tag to a class of the exact same name (no
        // "Component" suffix added), so without this, both tags 500 with
        // "Unable to find component" the moment either screen is opened —
        // the single biggest live bug found testing this batch, since it
        // blocks every carer's shift-visit Handover tab outright.
        Livewire::component('wellbeing.wellbeing-observation', \App\Livewire\Wellbeing\WellbeingObservationComponent::class);
        Livewire::component('handover.handover', \App\Livewire\Handover\HandoverComponent::class);

        // Batch 43: care invoices, payroll runs and shop activity post
        // themselves into the Finance ledger (App\Services\Accounting).
        \App\Services\Accounting\AutoPosting::register();
    }
}
