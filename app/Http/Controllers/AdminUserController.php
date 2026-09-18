<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::latest()->get()]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,user,member'],
            'is_subscribed' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        $data['is_subscribed'] = $request->boolean('is_subscribed');
        if ($data['password'] ?? false) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Anda tidak dapat menghapus akun sendiri.');
        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}
