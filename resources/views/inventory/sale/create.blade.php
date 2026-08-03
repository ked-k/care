@extends('inventory.layout')
@section('title', 'Add Sale')
@section('content')
    <div
        x-data="{
            customerModal: false,
            taxPercent: 10,
            shipping: 0,
            discount: 0,
            pay: '',
            selectedProduct: '',
            newCustomer: { name: '', phone: '', email: '', city: '', country: '' },
            products: [
                { id: 1, name: 'HeadPhone', price: 10 },
                { id: 2, name: 'Iphone 6', price: 570 },
                { id: 3, name: 'Leather Bag', price: 45 },
                { id: 4, name: 'Camera', price: 320 },
                { id: 5, name: 'Joystick', price: 35 },
                { id: 6, name: 'Jacket', price: 80 },
                { id: 7, name: 'Smart Watch', price: 150 },
                { id: 8, name: 'T-shirt', price: 20 },
                { id: 9, name: 'Helmet', price: 95 },
            ],
            items: [
                { name: 'HeadPhone', price: 10, qty: 5 },
                { name: 'Iphone 6', price: 570, qty: 1 },
            ],
            money(v) { return '$' + (parseFloat(v) || 0).toFixed(2); },
            addItem() {
                if (! this.selectedProduct) return;
                const p = this.products.find(p => p.id == this.selectedProduct);
                if (! p) return;
                const existing = this.items.find(i => i.name === p.name);
                if (existing) { existing.qty++; }
                else { this.items.push({ name: p.name, price: p.price, qty: 1 }); }
                this.selectedProduct = '';
            },
            removeItem(index) { this.items.splice(index, 1); },
            get subtotal() { return this.items.reduce((s, i) => s + (parseFloat(i.price) || 0) * (parseInt(i.qty) || 0), 0); },
            get taxAmount() { return this.subtotal * ((parseFloat(this.taxPercent) || 0) / 100); },
            get grandTotal() { return this.subtotal + this.taxAmount + (parseFloat(this.shipping) || 0) - (parseFloat(this.discount) || 0); },
            saveCustomer() {
                const name = this.newCustomer.name.trim();
                if (name) {
                    const select = this.$refs.customerSelect;
                    const opt = new Option(name, 'new-' + Date.now(), true, true);
                    select.add(opt);
                }
                this.newCustomer = { name: '', phone: '', email: '', city: '', country: '' };
                this.customerModal = false;
            },
        }"
    >
        <x-page-header title="Add Sale" subtitle="{{ __('Create a new sales entry') }}" icon="ik ik-shopping-cart"
                       :breadcrumbs="['Home' => url('dashboard'), 'Sales' => url('sales'), 'Add Sale' => null]" />

        <form method="POST" action="">
            @csrf
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                {{-- ============ LEFT: Sale meta ============ --}}
                <div class="lg:col-span-4 xl:col-span-3">
                    <x-card title="{{ __('Sale Details') }}" icon="ik ik-file-text">
                        <div class="space-y-4">
                            <div>
                                <label for="sale_date" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                                <div class="relative">
                                    <i class="ik ik-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input type="date" name="sale_date" id="sale_date"
                                           class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                </div>
                            </div>

                            <div>
                                <label for="customer_id" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Customer') }}</label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <select x-ref="customerSelect" name="customer_id" id="customer_id"
                                                class="w-full appearance-none rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-9 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                            <option value="">{{ __('Select Customer') }}</option>
                                            <option value="1">Alex Ferguson</option>
                                            <option value="2">John Doe</option>
                                        </select>
                                        <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <button type="button" @click="customerModal = true" title="{{ __('Add Customer') }}"
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500 text-white shadow-sm transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-200">
                                        <i class="ik ik-user-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <x-form.input name="reference_no" label="{{ __('Reference No') }}" placeholder="{{ __('Enter Reference No') }}" />

                            <div>
                                <label for="tax" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Tax (%)') }}</label>
                                <input type="number" name="tax" id="tax" min="0" step="0.01" x-model.number="taxPercent" placeholder="{{ __('Enter Tax') }}"
                                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                            </div>

                            <x-form.textarea name="note" label="{{ __('Note') }}" rows="4" placeholder="{{ __('Enter Note') }}" />
                        </div>
                    </x-card>
                </div>

                {{-- ============ RIGHT: Product picker + items + totals ============ --}}
                <div class="lg:col-span-8 xl:col-span-9">
                    <x-card title="{{ __('Products') }}" icon="ik ik-package">
                        {{-- Product picker --}}
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-4">
                                <div class="relative">
                                    <select name="warehouse_id"
                                            class="w-full appearance-none rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-9 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                        <option value="">{{ __('Select Warehouse') }}</option>
                                        <option value="1">Warehouse 1</option>
                                        <option value="2">Warehouse 2</option>
                                    </select>
                                    <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="sm:col-span-6">
                                <div class="relative">
                                    <select x-model="selectedProduct" @keydown.enter.prevent="addItem()"
                                            class="w-full appearance-none rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-9 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                        <option value="">{{ __('Select Product') }}</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name + ' — ' + money(p.price)"></option>
                                        </template>
                                    </select>
                                    <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <x-button type="button" variant="primary" class="w-full" @click="addItem()">
                                    <i class="ik ik-plus"></i> {{ __('Add') }}
                                </x-button>
                            </div>
                        </div>

                        {{-- Line items table --}}
                        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/60 text-left text-xs uppercase tracking-wide text-gray-400">
                                        <th class="px-5 py-3 font-medium">{{ __('SL') }}</th>
                                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                                        <th class="px-5 py-3 font-medium">{{ __('Unit Price') }}</th>
                                        <th class="px-5 py-3 font-medium">{{ __('Qty') }}</th>
                                        <th class="px-5 py-3 text-right font-medium">{{ __('Sub Total') }}</th>
                                        <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-3 text-gray-500" x-text="index + 1"></td>
                                            <td class="px-5 py-3 font-medium text-gray-700" x-text="item.name"></td>
                                            <td class="px-5 py-3 text-gray-600" x-text="money(item.price)"></td>
                                            <td class="px-5 py-3">
                                                <input type="number" min="1" x-model.number="item.qty"
                                                       class="w-20 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                            </td>
                                            <td class="px-5 py-3 text-right font-medium text-gray-700" x-text="money(item.price * item.qty)"></td>
                                            <td class="px-5 py-3 text-right">
                                                <button type="button" @click="removeItem(index)" title="{{ __('Remove') }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-accent-500 transition hover:bg-accent-50">
                                                    <i class="ik ik-trash-2"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    {{-- Empty state --}}
                                    <tr x-show="items.length === 0">
                                        <td colspan="6" class="px-5 py-10 text-center">
                                            <i class="ik ik-shopping-cart mb-2 block text-2xl text-gray-300"></i>
                                            <p class="text-sm text-gray-400">{{ __('No products added yet. Select a product and click Add.') }}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals panel --}}
                        <div class="mt-5 flex justify-end">
                            <div class="w-full max-w-sm space-y-2 rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ __('Subtotal') }}</span>
                                    <span class="font-medium text-gray-700" x-text="money(subtotal)"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ __('Tax') }} (<span x-text="(parseFloat(taxPercent)||0).toFixed(2)"></span>%)</span>
                                    <span class="font-medium text-gray-700" x-text="money(taxAmount)"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ __('Shipping') }}</span>
                                    <input type="number" min="0" step="0.01" name="shipping" x-model.number="shipping"
                                           class="w-24 rounded-lg border border-gray-200 bg-white px-2 py-1 text-right text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ __('Discount') }}</span>
                                    <input type="number" min="0" step="0.01" name="discount" x-model.number="discount"
                                           class="w-24 rounded-lg border border-gray-200 bg-white px-2 py-1 text-right text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                </div>
                                <div class="mt-1 flex items-center justify-between border-t border-gray-200 pt-3 text-base">
                                    <span class="font-semibold text-gray-700">{{ __('Grand Total') }}</span>
                                    <span class="font-bold text-primary-600" x-text="money(grandTotal)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Footer: status + actions --}}
                        <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 md:grid-cols-12">
                            <div class="md:col-span-3">
                                <x-form.select name="sale_status" label="{{ __('Sale Status') }}">
                                    <option value="">{{ __('Select Sale Status') }}</option>
                                    <option value="completed">{{ __('Completed') }}</option>
                                    <option value="shipped">{{ __('Shipped') }}</option>
                                    <option value="pending">{{ __('Pending') }}</option>
                                </x-form.select>
                            </div>
                            <div class="md:col-span-3">
                                <x-form.select name="payment_status" label="{{ __('Payment Status') }}">
                                    <option value="">{{ __('Select Payment Status') }}</option>
                                    <option value="pending">{{ __('Pending') }}</option>
                                    <option value="due">{{ __('Due') }}</option>
                                    <option value="paid">{{ __('Paid') }}</option>
                                </x-form.select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="pay" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Pay') }}</label>
                                <input type="number" name="pay" id="pay" min="0" step="0.01" x-model.number="pay" placeholder="{{ __('Amount') }}"
                                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-center text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                            </div>
                            <div class="flex items-end justify-end gap-2 md:col-span-4">
                                <x-button type="button" variant="outline">
                                    <i class="ik ik-eye"></i> {{ __('Preview Invoice') }}
                                </x-button>
                                <x-button type="submit" variant="primary">
                                    <i class="ik ik-save"></i> {{ __('Save') }}
                                </x-button>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </form>

        {{-- ============ Add Customer modal (inside x-data scope) ============ --}}
        <div x-show="customerModal" x-cloak x-transition.opacity
             @keydown.escape.window="customerModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div x-show="customerModal" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 @click.outside="customerModal = false"
                 class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <i class="ik ik-user-plus"></i>
                        </span>
                        <h5 class="font-semibold text-gray-700">{{ __('Add Customer') }}</h5>
                    </div>
                    <button type="button" @click="customerModal = false" class="text-gray-400 transition hover:text-gray-600">
                        <i class="ik ik-x text-lg"></i>
                    </button>
                </div>
                <div class="px-5 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                            <input type="text" x-model="newCustomer.name" placeholder="{{ __('Enter Customer Name') }}"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                            <input type="text" x-model="newCustomer.phone" placeholder="{{ __('Enter Phone') }}"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                            <input type="email" x-model="newCustomer.email" placeholder="{{ __('Enter Email') }}"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('City') }}</label>
                            <input type="text" x-model="newCustomer.city" placeholder="{{ __('Enter City') }}"
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Country') }}</label>
                            <div class="relative">
                                <select x-model="newCustomer.country"
                                        class="w-full appearance-none rounded-lg border border-gray-200 bg-white py-2.5 pl-3 pr-9 text-sm text-gray-700 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                                    <option value="">{{ __('Select Country') }}</option>
                                    {{-- Representative subset — replace with full country list / DB lookup in production --}}
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                    <option value="IN">India</option>
                                    <option value="BD">Bangladesh</option>
                                    <option value="JP">Japan</option>
                                    <option value="CN">China</option>
                                    <option value="BR">Brazil</option>
                                    <option value="AE">United Arab Emirates</option>
                                </select>
                                <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <x-button type="button" variant="outline" @click="customerModal = false">{{ __('Cancel') }}</x-button>
                    <x-button type="button" variant="primary" @click="saveCustomer()">
                        <i class="ik ik-check"></i> {{ __('Save') }}
                    </x-button>
                </div>
            </div>
        </div>
    </div>
@endsection
