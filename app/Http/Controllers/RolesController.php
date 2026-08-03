<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
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
            $permissions = Permission::pluck('name', 'id');
            $roles = Role::with('permissions')
                ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
                ->paginate($perPage)
                ->withQueryString();

            return view('roles', compact('permissions', 'roles'));
        } catch (\Exception $e) {
            $bug = $e->getMessage();

            return redirect()->back()->with('error', $bug);
        }
    }

    /**
     * Store new roles with assigned permission
     * Associate permissions will be stored in table
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function create(Request $request): RedirectResponse
    {
        try {
            $role = Role::create(['name' => $request->name]);
            $role->syncPermissions(Permission::whereKey($request->permissions ?? [])->get());

            if ($role) {
                return redirect('roles')->with('success', 'Role created succesfully!');
            }

            return redirect('roles')->with('error', 'Failed to create role! Try again.');
        } catch (\Exception $e) {
            $bug = $e->getMessage();

            return redirect()->back()->with('error', $bug);
        }
    }

    /**
     * Edit Role
     *
     * @param int $id
     * @return mixed
     */
    public function edit($id): mixed
    {
        $role = Role::where('id', $id)->first();

        if ($role) {
            $role_permission = $role->permissions()->pluck('id')->toArray();

            $permissions = Permission::pluck('name', 'id');

            return view('edit-roles', compact('role', 'role_permission', 'permissions'));
        }

        return redirect('404');
    }

    /**
     * update role
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->messages()->first());
        }

        try {
            $role = Role::find($request->id);

            $update = $role->update([
                'name' => $request->name,
            ]);

            // Sync role permissions
            $role->syncPermissions(Permission::whereKey($request->permissions ?? [])->get());

            return redirect('roles')->with('success', 'Role info updated succesfully!');
        } catch (\Exception $e) {
            $bug = $e->getMessage();

            return redirect()->back()->with('error', $bug);
        }
    }

    /**
     * Edit Role
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id): RedirectResponse
    {
        if ($role = Role::find($id)) {
            $delete = $role->delete();
            $perm = $role->permissions()->delete();

            return redirect('roles')->with('success', 'Role deleted!');
        }

        return redirect('404');
    }
}
