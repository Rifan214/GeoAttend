<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('workLocation')->orderBy('id', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($locationId = $request->input('work_location_id')) {
            $query->where('work_location_id', $locationId);
        }

        $users = $query->paginate(15)->withQueryString();
        $locations = WorkLocation::where('is_active', true)->get();

        return view('admin.users.index', [
            'users' => $users,
            'locations' => $locations,
            'roles' => Role::cases(),
        ]);
    }

    public function create(): View
    {
        $locations = WorkLocation::where('is_active', true)->get();

        return view('admin.users.create', [
            'locations' => $locations,
            'roles' => Role::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'work_location_id' => $request->work_location_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.user_created',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_object($user->role) ? $user->role->value : $user->role,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Karyawan {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        $locations = WorkLocation::all();

        return view('admin.users.edit', [
            'user' => $user,
            'locations' => $locations,
            'roles' => Role::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Prevent admin lockout
        if ($user->id === Auth::id()) {
            if ($request->role !== Role::ADMIN->value) {
                return back()->withInput()->with('error', 'Anda tidak dapat menurunkan role akun Anda sendiri untuk mencegah terkunci dari sistem.');
            }

            if (! $request->boolean('is_active', true)) {
                return back()->withInput()->with('error', 'Anda tidak dapat menonaktifkan akun administratif Anda sendiri.');
            }
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'work_location_id' => $request->work_location_id,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.user_updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => [
                'name' => $user->name,
                'role' => is_object($user->role) ? $user->role->value : $user->role,
                'is_active' => $user->is_active,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Data karyawan {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.user_status_toggled',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => [
                'is_active' => $user->is_active,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }
}
