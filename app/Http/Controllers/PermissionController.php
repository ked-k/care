<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Show Role List
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request): mixed
    {
        try {
            $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 10;
            $roles = Role::pluck('name', 'id');
            $permissions = Permission::with('roles')
                ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
                ->paginate($perPage)
                ->withQueryString();

            return view('permission', compact('roles', 'permissions'));
        } catch (\Exception $e) {
            $bug = $e->getMessage();

            return redirect()->back()->with('error', $bug);
        }
    }

    /**
     * Store new roles with assigned permission
     * Associate permissions will be stored in table
     *
     * @param PermissionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function create(PermissionRequest $request): RedirectResponse
    {
        try {
            $permission = Permission::create(['name' => $request->name]);
            $permission->syncRoles(Role::whereKey($request->roles ?? [])->get());

            if ($permission) {
                return redirect('permission')->with('success', 'Permission created succesfully!');
            }

            return redirect('permission')->with('error', 'Failed to create permission! Try again.');
        } catch (\Exception $e) {
            $bug = $e->getMessage();

            return redirect()->back()->with('error', $bug);
        }
    }

    /**
     * delete permission
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id): RedirectResponse
    {
        if ($permission = Permission::find($id)) {
            $delete = $permission->delete();
            $perm = $permission->roles()->delete();

            return redirect('permission')->with('success', 'Permission deleted!');
        }

        return redirect('404');
    }

    /**
     * get permission badges by role
     *
     * @param Request $request
     * @return mixed
     */
    public function getPermissionBadgeByRole(Request $request): mixed
    {
        $badge = 'inline-flex items-center rounded bg-gray-700 px-2 py-0.5 text-xs font-medium text-white mr-1 mb-1';
        $badges = '';
        $role = $request->id ? Role::find($request->id) : null;

        if ($role && $role->name == 'Super Admin') {
            return '<span class="text-sm text-gray-500">Super Admin has all the permissions!</span>';
        }

        if ($role) {
            foreach ($role->permissions()->pluck('name') as $permission) {
                $badges .= '<span class="' . $badge . '">' . e($permission) . '</span>';
            }
        }

        return $badges ?: '<span class="text-sm text-gray-400">No permissions for this role</span>';
    }
}
