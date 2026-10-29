<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class CategoryIndexComponent extends ShopAdminComponent
{
    use WithFileUploads;

    public ?string $editingId = null;
    public array $form = ['name' => '', 'slug' => '', 'description' => '', 'icon' => '', 'parent_id' => '', 'sort_order' => 0, 'is_featured' => false, 'is_active' => true];
    public $image = null;

    public function create(): void
    {
        $this->resetErrorBag();
        $this->editingId = null;
        $this->form = ['name' => '', 'slug' => '', 'description' => '', 'icon' => '', 'parent_id' => '', 'sort_order' => 0, 'is_featured' => false, 'is_active' => true];
        $this->image = null;
        $this->dispatch('open-drawer', 'category-form');
    }

    public function edit(string $id): void
    {
        $this->resetErrorBag();
        $c = ShopCategory::where('store_id', $this->store()->id)->findOrFail($id);
        $this->editingId = $c->id;
        $this->form = [
            'name' => $c->name, 'slug' => $c->slug, 'description' => (string) $c->description, 'icon' => (string) $c->icon,
            'parent_id' => (string) $c->parent_id, 'sort_order' => $c->sort_order, 'is_featured' => $c->is_featured, 'is_active' => $c->is_active,
        ];
        $this->image = null;
        $this->dispatch('open-drawer', 'category-form');
    }

    public function save(): void
    {
        $store = $this->store();
        $this->form['slug'] = Str::slug($this->form['slug'] ?: $this->form['name']);
        $this->validate([
            'form.name' => 'required|string|max:120',
            'form.slug' => ['required', 'string', 'max:140', Rule::unique('shop_categories', 'slug')->where('store_id', $store->id)->ignore($this->editingId)],
            'form.parent_id' => ['nullable', Rule::exists('shop_categories', 'id')->where('store_id', $store->id), Rule::notIn([$this->editingId])],
            'form.sort_order' => 'integer|min:0',
            'image' => 'nullable|image|max:4096',
        ]);

        $data = $this->form;
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['icon'] = $data['icon'] ?: null;
        if ($this->image) {
            $data['image_path'] = $this->image->store('shop/categories/'.$store->id, 'public');
        }

        $this->editingId
            ? ShopCategory::where('store_id', $store->id)->findOrFail($this->editingId)->update($data)
            : $store->categories()->create($data);

        $this->dispatch('close-drawer', 'category-form');
        $this->toast(__('Category saved.'));
    }

    public function toggleActive(string $id): void
    {
        $c = ShopCategory::where('store_id', $this->store()->id)->findOrFail($id);
        $c->update(['is_active' => ! $c->is_active]);
    }

    public function delete(string $id): void
    {
        $c = ShopCategory::where('store_id', $this->store()->id)->withCount('products')->findOrFail($id);
        if ($c->products_count > 0) {
            $this->toast(__('Move or delete this category’s :n products first.', ['n' => $c->products_count]), 'warning');

            return;
        }
        $c->delete();
        $this->editingId = null;
        $this->dispatch('close-drawer', 'category-form');
        $this->toast(__('Category deleted.'));
    }

    public function render()
    {
        $categories = ShopCategory::where('store_id', $this->store()->id)
            ->withCount(['products', 'products as active_count' => fn ($q) => $q->where('status', 'active')])
            ->with('parent')
            ->orderBy('sort_order')->orderBy('name')->get();

        return $this->adminPage('livewire.shop.admin.categories', compact('categories'), __('Categories'));
    }
}
