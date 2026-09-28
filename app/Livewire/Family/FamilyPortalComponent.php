<?php

namespace App\Livewire\Family;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.family')]
class FamilyPortalComponent extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Family'), 403);

        // Most family logins are only ever linked to one person — skip the
        // "pick who you want to see" step and go straight to their record,
        // the way a phone app opens straight to your content rather than a
        // menu. Anyone linked to more than one service user (e.g. two
        // parents) still gets the picker below.
        $links = Auth::user()->familyLinks()->get();
        if ($links->count() === 1) {
            $this->redirect(route('family.service-user', $links->first()->service_user_id), navigate: true);
        }
    }

    public function render()
    {
        $links = Auth::user()->familyLinks()->with('serviceUser')->get()
            ->map(function ($link) {
                $link->setAttribute('unread_messages', $link->serviceUser?->unreadMessagesCountForFamily() ?? 0);

                return $link;
            });

        return view('livewire.family.family-portal', ['links' => $links]);
    }
}
