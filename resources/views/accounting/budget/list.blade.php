@extends('accounting.layout')
@section('title', 'Budget Planner')
@section('content')

@php
$plans = [
    ['name' => 'Themicly Budget 2023',      'period' => 'Yearly',      'year' => '2023', 'id' => 1],
    ['name' => 'Themicly Budget Jan, 2023', 'period' => 'Monthly',     'year' => '2023', 'id' => 2],
    ['name' => 'Themicly Budget First 2022','period' => 'Half Yearly', 'year' => '2022', 'id' => 3],
];
@endphp

<x-page-header title="Budget Planner" subtitle="List" icon="ik ik-user-plus"
               :breadcrumbs="['Home' => url('accounting'), 'Budget Planner' => null]" />

<x-table title="Budget Planner" :total="100" :from="1" :to="3" :static-pages="5" search
         :add-url="url('budget-planner/create')" add-label="Add Plan">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Period</th>
                    <th class="px-5 py-3 font-medium">Year</th>
                    <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($plans as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                    <td class="px-5 py-3 font-medium text-gray-700">{{$item['name']}}</td>
                    <td class="px-5 py-3 text-gray-500">{{$item['period']}}</td>
                    <td class="px-5 py-3 text-gray-500">{{$item['year']}}</td>
                    <td class="px-5 py-3" data-no-export>
                        <div class="flex items-center gap-3">
                            <a href="{{url('budget-planner/edit/'.$item['id'])}}" class="text-green-500 hover:text-green-600"><i class="ik ik-edit"></i></a>
                            <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
</x-table>
@endsection
