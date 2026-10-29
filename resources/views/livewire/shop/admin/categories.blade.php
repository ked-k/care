<div>
    <x-page-header title="{{ __('Categories') }}" subtitle="{{ __('How products are grouped in the shop’s menu') }}" icon="ik ik-folder"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Categories' => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add category') }}</x-button>
    </x-page-header>

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('On sale') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('All products') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Order') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Shown') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($categories as $c)
                        <tr class="hover:bg-gray-50" wire:key="c-{{ $c->id }}">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="{{ $c->icon ?: \App\Models\Shop\ShopCategory::guessIcon($c->name) }}"></i></span>
                                    <div>
                                        <span class="font-medium text-gray-700">{{ $c->name }}</span>
                                        @if ($c->is_featured)<i class="ik ik-star ml-1 text-amber-500" title="{{ __('Featured') }}"></i>@endif
                                        <span class="block text-xs text-gray-400">/{{ $c->slug }}@if ($c->parent) — {{ __('in :parent', ['parent' => $c->parent->name]) }}@endif</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ number_format($c->active_count) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ number_format($c->products_count) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ $c->sort_order }}</td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleActive('{{ $c->id }}')" class="text-sm">
                                    <x-badge color="{{ $c->is_active ? 'success' : 'gray' }}">{{ $c->is_active ? __('Shown') : __('Hidden') }}</x-badge>
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <a href="{{ route('shop.admin.products', ['categoryFilter' => $c->id]) }}" wire:navigate class="text-sm text-gray-500 hover:underline">{{ __('Products') }}</a>
                                <button type="button" wire:click="edit('{{ $c->id }}')" class="ml-3 text-sm font-medium text-primary-600 hover:underline">{{ __('Edit') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10">
                            <x-empty-state title="{{ __('No categories yet') }}" description="{{ __('Categories become the menu across the top of your shop.') }}" icon="ik ik-folder" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="category-form" title="{{ $editingId ? __('Edit category') : __('Add category') }}" width="w-[28rem]">
        <form wire:submit="save" id="category-form" class="space-y-4">
            <x-form.input name="form.name" label="{{ __('Name') }}" wire:model="form.name" required />
            <x-form.input name="form.slug" label="{{ __('Web address') }}" wire:model="form.slug" hint="{{ __('Leave blank to make one from the name.') }}" />
            <x-form.select name="form.parent_id" label="{{ __('Inside another category') }}" wire:model="form.parent_id">
                <option value="">{{ __('No — top level') }}</option>
                @foreach ($categories->where('id', '!=', $editingId) as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </x-form.select>
            <x-form.textarea name="form.description" label="{{ __('Description') }}" wire:model="form.description" rows="3" />
            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="form.icon" label="{{ __('Icon') }}" wire:model="form.icon" placeholder="fas fa-mitten" hint="{{ __('Font Awesome class. Blank = automatic.') }}" />
                <x-form.input type="number" min="0" name="form.sort_order" label="{{ __('Menu order') }}" wire:model="form.sort_order" />
            </div>
            <div>
                <p class="mb-1.5 text-sm font-medium text-gray-700">{{ __('Image (optional)') }}</p>
                <input type="file" wire:model="image" accept="image/*" class="text-sm text-gray-600">
                @error('image')<p class="mt-1 text-xs text-accent-600">{{ $message }}</p>@enderror
            </div>
            <x-form.checkbox name="form.is_featured" label="{{ __('Show on the shop home page') }}" wire:model="form.is_featured" />
            <x-form.checkbox name="form.is_active" label="{{ __('Show in the shop') }}" wire:model="form.is_active" />
        </form>
        <x-slot:footer>
            @if ($editingId)
                <x-button variant="ghost" class="mr-auto" wire:click="delete('{{ $editingId }}')" wire:confirm="{{ __('Delete this category?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'category-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="category-form">{{ __('Save category') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
