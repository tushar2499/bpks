<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => ['required', Rule::in(['admin', 'operator', 'customer_care'])],
            'operator' => 'nullable|string',
        ]);

        $data['password'] = Hash::make($data['password']);
        if ($data['role'] !== 'operator') {
            $data['operator'] = null;
        }

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'ব্যবহারকারী তৈরি হয়েছে।');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role'     => ['required', Rule::in(['admin', 'operator', 'customer_care'])],
            'operator' => 'nullable|string',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($data['role'] !== 'operator') {
            $data['operator'] = null;
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'ব্যবহারকারী আপডেট হয়েছে।');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'নিজেকে মুছতে পারবেন না।');
        }

        $user->delete();

        return back()->with('success', 'ব্যবহারকারী মুছে ফেলা হয়েছে।');
    }
}
