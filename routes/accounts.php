<?php

use App\Http\Controllers\Accounting\AccountsPrintController;
use App\Livewire\Accounting\BankingComponent;
use App\Livewire\Accounting\ChartComponent;
use App\Livewire\Accounting\ContactIndexComponent;
use App\Livewire\Accounting\ContactShowComponent;
use App\Livewire\Accounting\DashboardComponent;
use App\Livewire\Accounting\DocumentFormComponent;
use App\Livewire\Accounting\DocumentIndexComponent;
use App\Livewire\Accounting\DocumentShowComponent;
use App\Livewire\Accounting\JournalFormComponent;
use App\Livewire\Accounting\JournalIndexComponent;
use App\Livewire\Accounting\JournalShowComponent;
use App\Livewire\Accounting\PaymentIndexComponent;
use App\Livewire\Accounting\PaymentShowComponent;
use App\Livewire\Accounting\ReportComponent;
use App\Livewire\Accounting\ReportIndexComponent;
use App\Livewire\Accounting\SettingsComponent;
use App\Livewire\Accounting\YearsComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance & Accounts workspace (Batch 43)
|--------------------------------------------------------------------------
| Included from routes/web.php inside the main staff ['auth', 'not-family']
| group. Every page under /accounts shows the Finance sidebar
| (config/menu.php 'accounts'). Care invoicing (/invoicing) and payroll
| (/payroll) stay where they are and post into this ledger automatically.
*/

Route::prefix('accounts')->name('accounts.')->middleware('can:manage_accounts')->group(function () {
    Route::get('/', DashboardComponent::class)->name('dashboard');

    Route::get('/invoices', DocumentIndexComponent::class)->name('invoices');
    Route::get('/bills', DocumentIndexComponent::class)->name('bills');
    Route::get('/money', DocumentIndexComponent::class)->name('money');
    Route::get('/documents/new/{kind}', DocumentFormComponent::class)->whereIn('kind', ['invoice', 'bill', 'money_in', 'money_out'])->name('documents.create');
    Route::get('/documents/{documentId}', DocumentShowComponent::class)->name('documents.show');
    Route::get('/documents/{documentId}/edit', DocumentFormComponent::class)->name('documents.edit');
    Route::get('/documents/{documentId}/print', [AccountsPrintController::class, 'document'])->name('documents.print');

    Route::get('/receipts', PaymentIndexComponent::class)->name('receipts');
    Route::get('/payments', PaymentIndexComponent::class)->name('payments');
    Route::get('/payments/{paymentId}', PaymentShowComponent::class)->name('payments.show');
    Route::get('/payments/{paymentId}/print', [AccountsPrintController::class, 'payment'])->name('payments.print');

    Route::get('/customers', ContactIndexComponent::class)->name('customers');
    Route::get('/suppliers', ContactIndexComponent::class)->name('suppliers');
    Route::get('/contacts/{contactId}', ContactShowComponent::class)->name('contacts.show');
    Route::get('/contacts/{contactId}/statement', [AccountsPrintController::class, 'statement'])->name('contacts.statement');

    Route::get('/banking', BankingComponent::class)->name('banking');

    Route::get('/journals', JournalIndexComponent::class)->name('journals');
    Route::get('/journals/new', JournalFormComponent::class)->name('journals.create');
    Route::get('/journals/{journalId}', JournalShowComponent::class)->name('journals.show');

    Route::get('/chart-of-accounts', ChartComponent::class)->name('chart');
    Route::get('/financial-years', YearsComponent::class)->name('years');

    Route::get('/reports', ReportIndexComponent::class)->name('reports');
    Route::get('/reports/{report}', ReportComponent::class)->name('reports.show');
    Route::get('/reports/{report}/print', [AccountsPrintController::class, 'report'])->name('reports.print');
    Route::get('/reports/{report}/csv', [AccountsPrintController::class, 'csv'])->name('reports.csv');

    Route::get('/settings', SettingsComponent::class)->name('settings');
});
