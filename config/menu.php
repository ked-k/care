<?php

/*
|--------------------------------------------------------------------------
| Sidebar Menus
|--------------------------------------------------------------------------
|
| The whole sidebar configuration lives here. Each menu is an ordered list
| of entries rendered by the <x-nav.menu> component. Supported keys:
|
|   'heading' => 'Section'                 // a section label
|   'label'   => 'Users'                   // visible text
|   'icon'    => 'ik ik-users'             // iconkit class
|   'route'   => 'dashboard'               // named route  (or…)
|   'url'     => 'users'                   // relative url
|   'active'  => 'users*|user/*'           // request()->is() pattern(s) that mark it active ('|' separated)
|   'can'     => 'manage_user'             // permission required to see the item (Gate::allows)
|   'badge'   => ['text' => 'New', 'color' => 'green']   // green | danger
|   'children'=> [ ...same shape... ]      // turns the entry into a collapsible group
|
| Reorganized (full redesign) to track the platform's own requirements
| list — client profiles, care delivery, rota/scheduling, medication,
| call monitoring/alerts, wellbeing/safeguarding, management reporting,
| security/audit, finance (timesheets/payroll/invoicing) — instead of
| growing by batch. See
| Claude outputs/CHANGES_Menu_Reorg.md for the full before/after and the
| "why nothing is missing" audit (carer-only accounts never render this
| file at all — they get layouts/carer.blade.php's own bottom nav — and
| every other absent route is a per-record drill-in reached by navigating
| into a list, e.g. a service user's own Medications/Assessments/Timeline/
| Consents/Family/Messages/Calls pages, or a shift's Visit screen).
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Top bar module switcher — jump between the sections (each has its own
    | sidebar). The first entry is treated as the default/fallback active.
    |----------------------------------------------------------------------
    */
    'top'  => [
        // Batch 40: now points at the care Dashboard (was the legacy
        // care-management.plan page) so switching back from the Shop
        // workspace lands somewhere useful. Still the default/fallback entry.
        ['label' => 'Care Management', 'desc' => 'Care, rota, people & finance', 'icon' => 'ik ik-heart', 'route' => 'dashboard', 'active' => 'care-management*'],
        // Batch 40: the agency's online shop — its own sidebar ('shop' below).
        // 'can' is honoured by include/topmenu.blade.php.
        ['label' => 'Shop', 'desc' => 'Online store, orders & stock', 'icon' => 'ik ik-shopping-bag', 'route' => 'shop.admin.dashboard', 'active' => 'shop-admin*', 'can' => 'manage_shop'],
        // Batch 43: the ledger, invoices & bills, bank, reports — sidebar 'accounts' below.
        ['label' => 'Finance & Accounts', 'desc' => 'Books, invoices, bank & reports', 'icon' => 'ik ik-pie-chart', 'route' => 'accounts.dashboard', 'active' => 'accounts*', 'can' => 'manage_accounts'],
        // ['label' => 'Main Dashboard', 'desc' => 'Overview & admin', 'icon' => 'ik ik-home', 'route' => 'dashboard', 'active' => 'dashboard'],
        // ['label' => 'Inventory', 'desc' => 'Products, stock & sales', 'icon' => 'ik ik-shopping-cart', 'url' => 'inventory', 'active' => 'inventory|products*|categories|sales*|purchases*|suppliers|customers'],
        // ['label' => 'Accounting', 'desc' => 'Income, expense & banking', 'icon' => 'ik ik-pie-chart', 'url' => 'accounting', 'active' => 'accounting|presale*|banking*|income*|expense*|budget-planner*|goal|assets'],
        // ['label' => 'POS', 'desc' => 'Point of sale terminal', 'icon' => 'ik ik-credit-card', 'url' => 'pos', 'active' => 'pos'],
    ],

    /*
    |----------------------------------------------------------------------
    | Care module sidebar
    |----------------------------------------------------------------------
    | Carer-only accounts (User::isCarerOnly()) never render this — they
    | get the separate carer bottom-nav layout (Batch 11-13). Everything
    | below is the admin/manager/super-admin experience only.
    */
    'care' => [

        // -- Quick-access single pages: frequently checked, worth one click
        // rather than a click into a group. --
        ['label' => 'Dashboard', 'icon' => 'ik ik-home', 'route' => 'dashboard', 'active' => 'dashboard'],

        ['label' => 'Notifications', 'icon' => 'ik ik-bell', 'route' => 'notifications.index', 'active' => 'notifications*'],

        // Batch 16: manager/admin-only in practice (AlertsManagerComponent::mount()
        // aborts for a plain carer) — no 'can' gate here since no permission
        // fits, same as 'Dashboard' above handling the carer/manager split itself.
        ['label' => 'Alerts', 'icon' => 'ik ik-alert-triangle', 'route' => 'alerts.index', 'active' => 'alerts.index'],

        // Batch 23: free-form multi-party conversations — mainly reached via
        // the floating "New conversation" button
        // (App\Livewire\Messaging\NewConversationComponent), but this is how
        // you find your way back to one afterwards. No 'can' gate: anyone
        // who lands in this sidebar at all (carer-only accounts never see
        // this file) can already have conversations of their own.
        ['label' => 'Conversations', 'icon' => 'ik ik-message-square', 'route' => 'conversations.index', 'active' => 'conversations*'],

        // Safeguarding stays its own top-level item rather than buried in a
        // group — incidents are high-priority and shouldn't need an extra
        // click to open.
        ['label' => 'Safeguarding', 'icon' => 'ik ik-shield', 'route' => 'safeguarding.index', 'active' => 'safeguarding*'],

        // -- People: who you're caring for and who's doing the caring. --
        ['label' => 'People', 'icon' => 'ik ik-users', 'active' => 'service-users*|staff*|agency*', 'children' => [
            ['label' => 'Service Users', 'icon' => 'ik ik-user', 'route' => 'service-users.index', 'active' => 'service-users.index'],
            ['label' => 'Staffs', 'icon' => 'ik ik-users', 'route' => 'staff.index', 'active' => 'staff.index', 'can' => 'manage_user'],
            ['label' => 'Agencies', 'icon' => 'ik ik-briefcase', 'route' => 'agency.settings', 'active' => 'agency.settings', 'can' => 'manage_user'],
        ]],

        // -- Care Delivery: the actual care-plan and task-level work. Per-
        // service-user pages (medications, assessments, timeline/handover,
        // consents, family, messages, calls) live under each person's own
        // record in Service Users above, not here. --
        ['label' => 'Care Delivery', 'icon' => 'ik ik-file-text', 'active' => 'care-plans*|tasks*', 'children' => [
            ['label' => 'Care Plans', 'icon' => 'ik ik-file-text', 'route' => 'care-plans.index', 'active' => 'care-plans.index'],
            ['label' => 'Tasks', 'icon' => 'ik ik-clipboard', 'route' => 'tasks.index', 'active' => 'tasks.index*'],
            // Batch 32: caseload-wide week view, follow-up to Batch 31's
            // per-client tab — manage_tasks-gated like the rest of this
            // group's operational items.
            ['label' => 'Task Chart', 'icon' => 'ik ik-calendar', 'route' => 'tasks.chart', 'active' => 'tasks.chart', 'can' => 'manage_tasks'],
        ]],

        // -- Rota & Scheduling. --
        ['label' => 'Rota $ Scheduling', 'icon' => 'ik ik-calendar', 'active' => 'rota*|my-rota', 'children' => [
            ['label' => 'My Rota', 'icon' => 'ik ik-calendar', 'route' => 'rota.mine', 'active' => 'my-rota'],
            ['label' => 'Rota Plans', 'icon' => 'ik ik-file-text', 'route' => 'rota.index', 'active' => 'rota.index', 'can' => 'manage_rota'],
            ['label' => 'Live-in Placements', 'icon' => 'ik ik-home', 'route' => 'rota.live-in', 'active' => 'rota.live-in', 'can' => 'manage_rota'],
            ['label' => 'Carer Availability', 'icon' => 'ik ik-calendar', 'route' => 'rota.availability', 'active' => 'rota.availability', 'can' => 'manage_rota'],
            ['label' => 'Recurring Templates', 'icon' => 'ik ik-repeat', 'route' => 'rota.recurring-templates', 'active' => 'rota.recurring-templates', 'can' => 'manage_rota'],
            ['label' => 'Visit History', 'icon' => 'ik ik-list', 'route' => 'rota.visit-history', 'active' => 'rota.visit-history', 'can' => 'manage_rota'],
        ]],

        // -- Finance: everything money-related that isn't carer scheduling
        // itself — paying carers (Payroll), billing payers (Invoicing,
        // Batch 21), and the timesheets both feed from. Was "Timesheets &
        // Payroll"; renamed once Invoicing joined it. --
        ['label' => 'Finance', 'icon' => 'ik ik-dollar-sign', 'active' => 'timesheets*|payroll*|invoicing*|invoices*', 'children' => [
            ['label' => 'Time sheets', 'icon' => 'ik ik-clipboard', 'route' => 'timesheets.index', 'active' => 'timesheets.index'],
            ['label' => 'Payroll', 'icon' => 'ik ik-dollar-sign', 'route' => 'payroll.index', 'active' => 'payroll.index'],
            // Batch 21
            ['label' => 'Invoicing', 'icon' => 'ik ik-file-text', 'route' => 'invoicing.index', 'active' => 'invoicing*|invoices*', 'can' => 'manage_invoicing'],
            // Batch 43: the full books — opens the Finance & Accounts workspace.
            ['label' => 'Accounts & reports', 'icon' => 'ik ik-pie-chart', 'route' => 'accounts.dashboard', 'can' => 'manage_accounts'],
        ]],

        // -- Compliance: policies, training and the day-to-day compliance
        // picture. Audit Log stays here (it's the compliance team's own
        // read) while GDPR-specific data-subject requests move to their
        // own group below. --
        ['label' => 'Compliance', 'icon' => 'ik ik-check-square', 'active' => 'policies*|training*|compliance*|audits*', 'children' => [
            ['label' => 'Policies', 'icon' => 'ik ik-file-text', 'route' => 'policies.index', 'active' => 'policies.index'],
            ['label' => 'Training', 'icon' => 'ik ik-award', 'route' => 'training.index', 'active' => 'training.index'],
            // Batch 36
            ['label' => 'Training Matrix', 'icon' => 'ik ik-grid', 'route' => 'training.matrix', 'active' => 'training.matrix', 'can' => 'training.manage'],
            ['label' => 'Compliance Dashboard', 'icon' => 'ik ik-check-square', 'route' => 'compliance.dashboard', 'active' => 'compliance.dashboard'],
            ['label' => 'Missing Documentation', 'icon' => 'ik ik-alert-triangle', 'route' => 'compliance.missing-documentation', 'active' => 'compliance.missing-documentation', 'can' => 'compliance.manage'],
            ['label' => 'Audit Log', 'icon' => 'ik ik-list', 'route' => 'audits.index', 'active' => 'audits.index', 'can' => 'compliance.manage'],
        ]],

        // -- Data Protection: GDPR-facing, split out of Compliance so it
        // doesn't get lost among the day-to-day policy/training items. --
        ['label' => 'Data Protection', 'icon' => 'ik ik-lock', 'active' => 'data-protection*', 'children' => [
            ['label' => 'Subject Access Requests', 'icon' => 'ik ik-file', 'route' => 'data-protection.sar', 'active' => 'data-protection.sar'],
            ['label' => 'Data Breaches', 'icon' => 'ik ik-alert-triangle', 'route' => 'data-protection.breaches', 'active' => 'data-protection.breaches'],
        ]],

    ],

    /*
    |----------------------------------------------------------------------
    | Shop workspace sidebar (Batch 40)
    |----------------------------------------------------------------------
    | Shown instead of 'care' on every /shop-admin page — see
    | layouts/sidebar.blade.php. Every route here is behind manage_shop.
    */
    'shop' => [
        ['label' => 'Shop dashboard', 'icon' => 'ik ik-bar-chart-2', 'route' => 'shop.admin.dashboard', 'active' => 'shop-admin'],
        ['label' => 'Orders', 'icon' => 'ik ik-shopping-cart', 'route' => 'shop.admin.orders', 'active' => 'shop-admin/orders*'],

        ['heading' => 'Catalogue'],
        ['label' => 'Products', 'icon' => 'ik ik-package', 'route' => 'shop.admin.products', 'active' => 'shop-admin/products*'],
        ['label' => 'Categories', 'icon' => 'ik ik-folder', 'route' => 'shop.admin.categories', 'active' => 'shop-admin/categories*'],

        ['heading' => 'Stock'],
        ['label' => 'Inventory', 'icon' => 'ik ik-layers', 'route' => 'shop.admin.inventory', 'active' => 'shop-admin/inventory*'],
        ['label' => 'Goods received', 'icon' => 'ik ik-download', 'route' => 'shop.admin.receiving', 'active' => 'shop-admin/receiving*'],
        ['label' => 'Suppliers', 'icon' => 'ik ik-briefcase', 'route' => 'shop.admin.suppliers', 'active' => 'shop-admin/suppliers*'],

        ['heading' => 'Selling'],
        ['label' => 'Customers', 'icon' => 'ik ik-users', 'route' => 'shop.admin.customers', 'active' => 'shop-admin/customers*'],
        ['label' => 'Discount codes', 'icon' => 'ik ik-tag', 'route' => 'shop.admin.discounts', 'active' => 'shop-admin/discounts*'],
        ['label' => 'Delivery options', 'icon' => 'ik ik-truck', 'route' => 'shop.admin.delivery', 'active' => 'shop-admin/delivery*'],

        ['heading' => 'Shop'],
        ['label' => 'Settings & payments', 'icon' => 'ik ik-settings', 'route' => 'shop.admin.settings', 'active' => 'shop-admin/settings*'],
        ['label' => 'View storefront', 'icon' => 'ik ik-external-link', 'route' => 'shop.admin.storefront'],
    ],

    /*
    |----------------------------------------------------------------------
    | Finance & Accounts workspace sidebar (Batch 43)
    |----------------------------------------------------------------------
    | Shown instead of 'care' on every /accounts page. Every route here is
    | behind manage_accounts. Care invoicing and payroll are linked at the
    | bottom: they stay in the care workspace and post here automatically.
    */
    'accounts' => [
        ['label' => 'Finance dashboard', 'icon' => 'ik ik-pie-chart', 'route' => 'accounts.dashboard', 'active' => 'accounts'],

        ['heading' => 'Sales'],
        ['label' => 'Sales invoices', 'icon' => 'ik ik-file-text', 'route' => 'accounts.invoices', 'active' => 'accounts/invoices*|accounts/documents/new/invoice'],
        ['label' => 'Receipts', 'icon' => 'ik ik-download', 'route' => 'accounts.receipts', 'active' => 'accounts/receipts*'],
        ['label' => 'Customers', 'icon' => 'ik ik-users', 'route' => 'accounts.customers', 'active' => 'accounts/customers*'],

        ['heading' => 'Purchases'],
        ['label' => 'Supplier bills', 'icon' => 'ik ik-file-minus', 'route' => 'accounts.bills', 'active' => 'accounts/bills*|accounts/documents/new/bill'],
        ['label' => 'Supplier payments', 'icon' => 'ik ik-upload', 'route' => 'accounts.payments', 'active' => 'accounts/payments'],
        ['label' => 'Suppliers', 'icon' => 'ik ik-truck', 'route' => 'accounts.suppliers', 'active' => 'accounts/suppliers*'],

        ['heading' => 'Bank & books'],
        ['label' => 'Bank & cash', 'icon' => 'ik ik-credit-card', 'route' => 'accounts.banking', 'active' => 'accounts/banking*'],
        ['label' => 'Money in / out', 'icon' => 'ik ik-repeat', 'route' => 'accounts.money', 'active' => 'accounts/money*|accounts/documents/new/money*'],
        ['label' => 'Journals', 'icon' => 'ik ik-book', 'route' => 'accounts.journals', 'active' => 'accounts/journals*'],
        ['label' => 'Chart of accounts', 'icon' => 'ik ik-list', 'route' => 'accounts.chart', 'active' => 'accounts/chart-of-accounts*'],

        ['heading' => 'Reports & year end'],
        ['label' => 'Reports', 'icon' => 'ik ik-bar-chart-2', 'route' => 'accounts.reports', 'active' => 'accounts/reports*'],
        ['label' => 'Financial years', 'icon' => 'ik ik-calendar', 'route' => 'accounts.years', 'active' => 'accounts/financial-years*'],
        ['label' => 'Settings', 'icon' => 'ik ik-settings', 'route' => 'accounts.settings', 'active' => 'accounts/settings*'],

        ['heading' => 'Posts here automatically'],
        ['label' => 'Care invoicing', 'icon' => 'ik ik-file-text', 'route' => 'invoicing.index', 'can' => 'manage_invoicing'],
        ['label' => 'Payroll', 'icon' => 'ik ik-dollar-sign', 'route' => 'payroll.index'],
    ],
];
