@extends('accounting.layout')
@section('title', 'Proposal')
@section('content')
    <x-page-header title="Presale" subtitle="Proposal" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Presale' => null, 'Proposal' => null]" />

    <x-table title="Proposal" :total="100" :from="1" :to="6" :static-pages="5" search
             :add-url="url('presale/proposal/create')" add-label="Add Proposal">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall"></th>
                        <th class="px-5 py-3 font-medium">ID</th>
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Category</th>
                        <th class="px-5 py-3 font-medium">Issue Date</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $proposals = [
                            ['PRO000001', 'Alex', 'Feb 12, 2023', 'draft'],
                            ['PRO000002', 'Alex', 'Feb 11, 2023', 'draft'],
                            ['PRO000003', 'Michael', 'Feb 9, 2023', 'sent'],
                            ['PRO000004', 'Jessica', 'Feb 8, 2023', 'paid'],
                            ['PRO000005', 'Emily', 'Feb 6, 2023', 'overdue'],
                            ['PRO000006', 'David', 'Feb 5, 2023', 'partial'],
                        ];
                    @endphp
                    @foreach ($proposals as [$no, $customer, $date, $status])
                        <tr class="{{ $status === 'overdue' ? 'hover:bg-gray-50 border-l-2 border-accent-500' : 'hover:bg-gray-50' }}">
                            <td class="px-5 py-3" data-no-export><input type="checkbox" class="select_all_child"></td>
                            <td class="px-5 py-3 font-medium text-gray-700">#{{ $no }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $customer }}</td>
                            <td class="px-5 py-3 text-gray-600">Sale</td>
                            <td class="px-5 py-3 text-gray-600">{{ $date }}</td>
                            <td class="px-5 py-3"><x-status-pill :status="$status" /></td>
                            <td class="px-5 py-3" data-no-export>
                                <div class="flex items-center gap-3">
                                    <a href="#!" class="text-primary-600 hover:text-primary-700"><i class="ik ik-eye f-16"></i></a>
                                    <a href="{{ url('presale/proposal/edit/1') }}" class="text-green-500 hover:text-green-600"><i class="ik ik-edit f-16"></i></a>
                                    <a href="#!" class="text-amber-500 hover:text-amber-600"><i class="ik ik-copy f-16"></i></a>
                                    <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2 f-16"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </x-table>
@endsection
