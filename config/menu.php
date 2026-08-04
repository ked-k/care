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
*/

return [

    /*
    |----------------------------------------------------------------------
    | Top bar module switcher — jump between the sections (each has its own
    | sidebar). The first entry is treated as the default/fallback active.
    |----------------------------------------------------------------------
    */
    'top'  => [
        ['label' => 'Care Management', 'desc' => 'Manage care plans', 'icon' => 'ik ik-heart', 'route' => 'care-management.plan', 'active' => 'care-management*'],
        // ['label' => 'Main Dashboard', 'desc' => 'Overview & admin', 'icon' => 'ik ik-home', 'route' => 'dashboard', 'active' => 'dashboard'],
        // ['label' => 'Inventory', 'desc' => 'Products, stock & sales', 'icon' => 'ik ik-shopping-cart', 'url' => 'inventory', 'active' => 'inventory|products*|categories|sales*|purchases*|suppliers|customers'],
        // ['label' => 'Accounting', 'desc' => 'Income, expense & banking', 'icon' => 'ik ik-pie-chart', 'url' => 'accounting', 'active' => 'accounting|presale*|banking*|income*|expense*|budget-planner*|goal|assets'],
        // ['label' => 'POS', 'desc' => 'Point of sale terminal', 'icon' => 'ik ik-credit-card', 'url' => 'pos', 'active' => 'pos'],
    ],

    /*
    |----------------------------------------------------------------------
    | Care module sidebar
    |----------------------------------------------------------------------
    */
    'care' => [
        ['label' => 'Dashboard', 'icon' => 'ik ik-home', 'route' => 'dashboard', 'active' => 'dashboard'],

        ['label' => 'Care Management', 'icon' => 'ik ik-file', 'active' => 'care-management/manage*', 'children' => [
            ['label' => 'Care Plans', 'icon' => 'ik ik-file-text', 'route' => 'care-plans.index', 'active' => 'care-plans.index'],
            ['label' => 'Tasks', 'icon' => 'ik ik-clipboard', 'route' => 'tasks.index', 'active' => 'tasks.index*'],
        ]],

        ['label' => 'Rota Management', 'icon' => 'ik ik-file', 'active' => 'rota*', 'children' => [
            ['label' => 'Rota Plans', 'icon' => 'ik ik-file-text', 'route' => 'rota.index', 'active' => 'rota.index'],
            ['label' => 'Time sheets', 'icon' => 'ik ik-clipboard', 'route' => 'timesheets.index', 'active' => 'timesheets.index'],
            ['label' => 'Payroll', 'icon' => 'ik ik-clipboard', 'route' => 'payroll.index', 'active' => 'payroll.index'],
        ]],
        ['label' => 'User Management', 'icon' => 'ik ik-file', 'active' => 'rota*', 'children' => [
            ['label' => 'Service Users', 'icon' => 'ik ik-file-text', 'route' => 'service-users.index', 'active' => 'service-users.index'],
            ['label' => 'Staffs', 'icon' => 'ik ik-clipboard', 'route' => 'staff.index', 'active' => 'staff.index'],
            ['label' => 'Agencies', 'icon' => 'ik ik-clipboard', 'route' => 'agency.settings', 'active' => 'agency.settings'],
        ]],

    ],
];
