<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdminUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';
        $users = User::query()->withCount('projects')
            ->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            })->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(SaveAdminUserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->except('is_admin'));
        $user->is_admin = $request->boolean('is_admin');
        $user->save();

        return to_route('admin.users.index')->with('status', 'Đã tạo người dùng.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(SaveAdminUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except('is_admin');
        if (! $request->filled('password')) {
            unset($data['password']);
        }
        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->is_admin = $request->boolean('is_admin');
        $user->save();

        return to_route('admin.users.index')->with('status', 'Đã cập nhật người dùng.');
    }
}
