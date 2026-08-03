<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>POS | {{ config('app.name') }} — {{ config('app.tagline') }}</title>
	@include('include.head')
</head>
@php
	$products = array_values(config('mockdata.products'));
	$categories = collect($products)->pluck('category_name')->filter()->unique()->values()->all();
@endphp
<body class="h-screen overflow-hidden bg-body font-sans text-[15px] text-gray-700 antialiased">
	<div x-data="pos(@js($products), @js($categories))" class="flex h-screen">

		<!-- Rail -->
		<div class="flex w-16 shrink-0 flex-col items-center gap-5 border-r border-gray-100 bg-white py-5">
			<a href="{{ url('/inventory') }}" title="{{ __('Back') }}" class="text-gray-400 hover:text-primary-600"><i class="ik ik-arrow-left-circle text-xl"></i></a>
			<button @click="$dispatch('open-command')" title="{{ __('Search') }}" class="text-gray-400 hover:text-primary-600"><i class="ik ik-search text-xl"></i></button>
			<button @click="held.length && (showHeld = true)" class="relative text-gray-400 hover:text-primary-600" title="{{ __('Held orders') }}">
				<i class="ik ik-clock text-xl"></i>
				<span x-show="held.length" x-cloak class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-semibold text-white" x-text="held.length"></span>
			</button>
			<a href="{{ url('profile') }}" class="text-gray-400 hover:text-primary-600" title="{{ __('Profile') }}"><i class="ik ik-user text-xl"></i></a>
			<a href="{{ url('logout') }}" class="mt-auto text-gray-400 hover:text-accent-600" title="{{ __('Logout') }}"><i class="ik ik-power text-xl"></i></a>
		</div>

		<!-- Products -->
		<div class="flex flex-1 flex-col overflow-hidden bg-white">
			<div class="flex flex-col gap-3 border-b border-gray-100 p-4">
				<div class="flex flex-col gap-3 sm:flex-row">
					<select x-model="warehouse" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 sm:w-48">
						<option value="">{{ __('All Warehouses') }}</option>
						<option value="1">{{ __('Warehouse 1') }}</option>
						<option value="2">{{ __('Warehouse 2') }}</option>
					</select>
					<div class="relative flex-1">
						<i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
						<input type="text" x-model="search" @keydown.enter="addFirstMatch()" x-ref="search"
						       placeholder="{{ __('Search or scan barcode, then Enter') }}"
						       class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
					</div>
				</div>
				<!-- Category chips -->
				<div class="flex flex-wrap gap-2">
					<button @click="category = ''" :class="category === '' ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
					        class="rounded-full px-3.5 py-1.5 text-sm font-medium transition">{{ __('All') }}</button>
					<template x-for="c in categories" :key="c">
						<button @click="category = c" :class="category === c ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
						        class="rounded-full px-3.5 py-1.5 text-sm font-medium transition" x-text="c"></button>
					</template>
				</div>
			</div>

			<div class="grid flex-1 auto-rows-min grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
				<template x-for="product in filtered" :key="product.id">
					<button type="button" @click="addToCart(product)"
					        class="group flex flex-col overflow-hidden rounded-xl border border-gray-100 text-left transition hover:border-primary-400 hover:shadow-md active:scale-[0.98]">
						<div class="relative aspect-square w-full overflow-hidden bg-gray-50">
							<img :src="product.image" :alt="product.name" class="h-full w-full object-cover transition group-hover:scale-105">
							<span x-show="product.offer_price" x-cloak class="absolute left-2 top-2 rounded bg-accent-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ __('Offer') }}</span>
						</div>
						<div class="flex flex-1 flex-col p-2.5">
							<p class="truncate text-sm font-medium text-gray-700" x-text="product.name"></p>
							<p class="truncate text-xs text-gray-400" x-text="product.category_name"></p>
							<div class="mt-1.5">
								<template x-if="product.offer_price">
									<span><span class="font-semibold text-primary-600" x-text="money(product.offer_price)"></span> <small class="text-accent-500 line-through" x-text="money(product.regular_price)"></small></span>
								</template>
								<template x-if="!product.offer_price">
									<span class="font-semibold text-primary-600" x-text="money(product.regular_price)"></span>
								</template>
							</div>
						</div>
					</button>
				</template>
				<div x-show="filtered.length === 0" x-cloak class="col-span-full flex flex-col items-center justify-center py-20 text-center text-gray-300">
					<i class="ik ik-search mb-3 text-4xl"></i>
					<p class="text-sm">{{ __('No products match') }}</p>
				</div>
			</div>
		</div>

		<!-- Cart -->
		<div class="flex w-[22rem] shrink-0 flex-col border-l border-gray-100 bg-white">
			<div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
				<h5 class="flex items-center gap-2 font-semibold text-gray-700"><i class="ik ik-shopping-cart text-primary-600"></i> {{ __('Current Order') }}</h5>
				<button @click="clearCart()" x-show="lines.length" x-cloak class="text-sm text-accent-500 hover:text-accent-600">{{ __('Clear') }}</button>
			</div>

			<!-- Customer -->
			<div class="border-b border-gray-100 px-5 py-3">
				<div class="flex items-center gap-3">
					<span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-primary-600"><i class="ik ik-user"></i></span>
					<input type="text" x-model="customer" placeholder="{{ __('Walk-in customer') }}"
					       class="flex-1 border-0 bg-transparent p-0 text-sm font-medium text-gray-700 outline-none placeholder:font-normal placeholder:text-gray-400">
				</div>
			</div>

			<!-- Lines -->
			<div class="flex-1 overflow-y-auto px-3 py-2">
				<template x-if="lines.length === 0">
					<div class="flex flex-col items-center justify-center py-16 text-center text-gray-300">
						<i class="ik ik-shopping-bag mb-3 text-4xl"></i>
						<p class="text-sm">{{ __('Cart is empty') }}</p>
						<p class="text-xs">{{ __('Tap a product to add it') }}</p>
					</div>
				</template>
				<template x-for="item in lines" :key="item.id">
					<div class="mb-1 flex items-center gap-3 rounded-xl p-2 hover:bg-gray-50">
						<img :src="item.image" class="h-12 w-12 rounded-lg object-cover">
						<div class="min-w-0 flex-1">
							<p class="truncate text-sm font-medium text-gray-700" x-text="item.name"></p>
							<p class="text-xs text-gray-400" x-text="money(item.price)"></p>
						</div>
						<div class="flex items-center gap-1.5">
							<button @click="dec(item.id)" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100"><i class="ik ik-minus text-xs"></i></button>
							<span class="w-6 text-center text-sm font-medium" x-text="item.quantity"></span>
							<button @click="inc(item.id)" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100"><i class="ik ik-plus text-xs"></i></button>
						</div>
						<span class="w-16 text-right text-sm font-semibold text-gray-700" x-text="money(item.subtotal)"></span>
					</div>
				</template>
			</div>

			<!-- Totals -->
			<div class="space-y-1.5 border-t border-gray-100 px-5 py-3 text-sm">
				<div class="flex justify-between text-gray-500"><span>{{ __('Subtotal') }}</span><span x-text="money(subtotal)"></span></div>
				<div class="flex items-center justify-between text-gray-500">
					<span>{{ __('Discount') }} (%)</span>
					<input type="number" min="0" max="100" x-model.number="discountPct" class="w-16 rounded-lg border border-gray-200 px-2 py-1 text-right text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
				</div>
				<div class="flex justify-between text-gray-500"><span>{{ __('Tax') }} (<span x-text="taxRate"></span>%)</span><span x-text="money(taxAmount)"></span></div>
				<div class="flex justify-between border-t border-gray-100 pt-2 text-lg font-bold text-gray-800"><span>{{ __('Total') }}</span><span x-text="money(total)"></span></div>
			</div>

			<div class="grid grid-cols-2 gap-2 border-t border-gray-100 p-4">
				<button @click="hold()" :disabled="!lines.length" class="rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40"><i class="ik ik-clock"></i> {{ __('Hold') }}</button>
				<button @click="openPayment()" :disabled="!lines.length" class="rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-40"><i class="ik ik-credit-card"></i> {{ __('Charge') }} <span x-text="money(total)"></span></button>
			</div>
		</div>

		<!-- Payment modal -->
		<div x-show="payOpen" x-transition.opacity x-cloak @keydown.escape.window="payOpen = false"
		     class="fixed inset-0 z-[90] flex items-center justify-center bg-black/40 p-4">
			<div x-trap.noscroll="payOpen" @click.outside="payOpen = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
				<div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
					<h5 class="font-semibold text-gray-800">{{ __('Payment') }}</h5>
					<button @click="payOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
				</div>
				<div class="p-5">
					<div class="mb-4 rounded-xl bg-primary-50 p-4 text-center">
						<p class="text-sm text-primary-700/70">{{ __('Total Due') }}</p>
						<p class="text-3xl font-bold text-primary-700" x-text="money(total)"></p>
					</div>

					<div class="mb-4 grid grid-cols-3 gap-2">
						<template x-for="m in ['Cash','Card','Wallet']" :key="m">
							<button @click="method = m" :class="method === m ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
							        class="rounded-lg border py-2.5 text-sm font-medium transition" x-text="m"></button>
						</template>
					</div>

					<label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Amount tendered') }}</label>
					<input type="number" x-model.number="tendered" class="mb-3 w-full rounded-lg border border-gray-200 px-3 py-2.5 text-right text-lg font-semibold outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">

					<div class="mb-4 grid grid-cols-4 gap-2">
						<template x-for="amt in quickAmounts" :key="amt">
							<button @click="tendered = amt" class="rounded-lg border border-gray-200 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" x-text="money(amt)"></button>
						</template>
						<button @click="tendered = total" class="col-span-4 rounded-lg border border-gray-200 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">{{ __('Exact') }} <span x-text="money(total)"></span></button>
					</div>

					<div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
						<span class="text-sm text-gray-500">{{ __('Change due') }}</span>
						<span class="text-lg font-bold" :class="change >= 0 ? 'text-green-600' : 'text-accent-600'" x-text="money(Math.max(0, change))"></span>
					</div>
				</div>

				<div class="flex gap-3 border-t border-gray-100 px-5 py-4">
					<button @click="payOpen = false" class="flex-1 rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">{{ __('Cancel') }}</button>
					<button @click="completeSale()" :disabled="method === 'Cash' && change < 0" class="flex-1 rounded-lg bg-green-500 py-2.5 text-sm font-semibold text-white transition hover:bg-green-600 disabled:opacity-40"><i class="ik ik-check"></i> {{ __('Complete Sale') }}</button>
				</div>
			</div>
		</div>

		<!-- Held orders modal -->
		<div x-show="showHeld" x-transition.opacity x-cloak @keydown.escape.window="showHeld = false"
		     class="fixed inset-0 z-[90] flex items-center justify-center bg-black/40 p-4">
			<div @click.outside="showHeld = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
				<div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
					<h5 class="font-semibold text-gray-800">{{ __('Held Orders') }}</h5>
					<button @click="showHeld = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
				</div>
				<div class="max-h-96 overflow-y-auto p-3">
					<template x-for="(h, i) in held" :key="i">
						<button @click="resume(i)" class="mb-1 flex w-full items-center justify-between rounded-xl px-4 py-3 text-left hover:bg-gray-50">
							<div>
								<p class="text-sm font-medium text-gray-700" x-text="h.customer || '{{ __('Walk-in customer') }}'"></p>
								<p class="text-xs text-gray-400"><span x-text="h.count"></span> {{ __('items') }} · <span x-text="h.time"></span></p>
							</div>
							<span class="font-semibold text-primary-600" x-text="money(h.total)"></span>
						</button>
					</template>
				</div>
			</div>
		</div>
	</div>

	@include('include.modalmenu')
	<x-command-palette />
	<x-toast />
	<x-confirm-modal />

	<script>
		function pos(products, categories) {
			return {
				products, categories,
				search: '', category: '', warehouse: '',
				cart: {}, discountPct: 0, taxRate: 10,
				customer: '',
				held: [], showHeld: false,
				payOpen: false, method: 'Cash', tendered: 0,
				money(v) { return '$' + (parseFloat(v) || 0).toFixed(2); },
				get filtered() {
					return this.products.filter((p) => {
						const okCat = !this.category || p.category_name === this.category;
						const okSearch = !this.search || (p.name || '').toLowerCase().includes(this.search.toLowerCase());
						return okCat && okSearch;
					});
				},
				get lines() { return Object.values(this.cart); },
				get subtotal() { return this.lines.reduce((s, i) => s + i.subtotal, 0); },
				get discountAmount() { return this.subtotal * (this.discountPct || 0) / 100; },
				get taxAmount() { return (this.subtotal - this.discountAmount) * this.taxRate / 100; },
				get total() { return Math.max(0, this.subtotal - this.discountAmount + this.taxAmount); },
				get change() { return (this.tendered || 0) - this.total; },
				get quickAmounts() {
					const t = Math.ceil(this.total);
					return [t, Math.ceil(t / 5) * 5, Math.ceil(t / 10) * 10, Math.ceil(t / 50) * 50]
						.filter((v, i, a) => a.indexOf(v) === i);
				},
				addToCart(product) {
					const price = parseFloat(product.offer_price || product.regular_price) || 0;
					const id = product.id;
					if (this.cart[id]) this.cart[id].quantity++;
					else this.cart[id] = { id, name: product.name, image: product.image, price, quantity: 1 };
					this.cart[id].subtotal = price * this.cart[id].quantity;
				},
				inc(id) { this.cart[id].quantity++; this.cart[id].subtotal = this.cart[id].price * this.cart[id].quantity; },
				dec(id) {
					if (this.cart[id].quantity <= 1) { delete this.cart[id]; return; }
					this.cart[id].quantity--; this.cart[id].subtotal = this.cart[id].price * this.cart[id].quantity;
				},
				addFirstMatch() {
					const m = this.filtered[0];
					if (m) { this.addToCart(m); this.search = ''; }
				},
				clearCart() {
					this.$store.confirm.open({
						title: '{{ __('Clear cart?') }}', message: '{{ __('All items in the current order will be removed.') }}',
						confirmText: '{{ __('Clear') }}',
						onConfirm: () => { this.cart = {}; this.discountPct = 0; this.customer = ''; },
					});
				},
				hold() {
					this.held.push({
						cart: JSON.parse(JSON.stringify(this.cart)), customer: this.customer,
						discountPct: this.discountPct, count: this.lines.length, total: this.total,
						time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
					});
					this.cart = {}; this.discountPct = 0; this.customer = '';
					window.toast('{{ __('Order held') }}', 'info');
				},
				resume(i) {
					const h = this.held[i];
					this.cart = h.cart; this.customer = h.customer; this.discountPct = h.discountPct;
					this.held.splice(i, 1); this.showHeld = false;
				},
				openPayment() { this.tendered = Math.ceil(this.total); this.method = 'Cash'; this.payOpen = true; },
				completeSale() {
					window.toast('{{ __('Sale completed') }} · ' + this.money(this.total), 'success');
					this.cart = {}; this.discountPct = 0; this.customer = ''; this.payOpen = false;
				},
			};
		}
	</script>
	@include('include.script')
</body>
</html>
