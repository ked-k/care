<?php

use App\Livewire\Invoicing\InvoiceRunIndexComponent;
use App\Livewire\Invoicing\InvoiceRunShowComponent;
use App\Livewire\Invoicing\InvoiceShowComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoicing routes (Batch 21)
|--------------------------------------------------------------------------
| Billing whoever pays for a service user's care — self-funded client/
| family, local authority, or a split between the two — distinct from
| Payroll (routes/rota-payroll.php), which pays carers. Require this file
| from routes/web.php inside the main staff auth group, same pattern as
| rota-payroll.php.
*/

Route::get('/invoicing', InvoiceRunIndexComponent::class)->name('invoicing.index');

Route::get('/invoicing/{invoiceRunId}', InvoiceRunShowComponent::class)->name('invoicing.show');

Route::get('/invoices/{invoice}', InvoiceShowComponent::class)->name('invoices.show');
