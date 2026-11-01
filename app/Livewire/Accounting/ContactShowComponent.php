<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Services\Accounting\Reports;
use Livewire\Attributes\Url;

/** Batch 43: a customer's or supplier's account — open items and a statement. */
class ContactShowComponent extends AccountsComponent
{
    public string $contactId;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(string $contactId): void
    {
        $this->contactId = Contact::where('agency_id', $this->agencyId())->findOrFail($contactId)->id;
        $this->from = $this->from ?: today()->subMonths(3)->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function render()
    {
        $c = Contact::where('agency_id', $this->agencyId())->with('serviceUser:id,name')->findOrFail($this->contactId);
        $reports = new Reports($this->settings());
        $statement = $reports->statement($c, $this->from ?: today()->subMonths(3)->toDateString(), $this->to ?: today()->toDateString());

        return $this->page('livewire.accounting.contact-show', [
            'c' => $c,
            'statement' => $statement,
            'open' => Document::where('agency_id', $this->agencyId())->where('contact_id', $c->id)->whereIn('status', ['posted', 'partial'])->orderBy('due_date')->get(),
            'recent' => Document::where('agency_id', $this->agencyId())->where('contact_id', $c->id)->latest('issue_date')->limit(10)->get(),
        ], $c->name);
    }
}
