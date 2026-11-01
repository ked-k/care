<?php

namespace App\Livewire\Accounting;

use App\Services\Accounting\ReportBuilder;

/** Batch 43: the list of financial reports. */
class ReportIndexComponent extends AccountsComponent
{
    public function render()
    {
        $s = $this->settings();

        return $this->page('livewire.accounting.reports', [
            'reports' => collect(ReportBuilder::REPORTS)->map(fn ($r, $k) => ['title' => ReportBuilder::title($s, $k), 'desc' => __($r[1]), 'icon' => $r[2]]),
        ], __('Reports'));
    }
}
