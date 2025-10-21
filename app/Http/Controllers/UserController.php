<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('role')->get());
    }

    public function show($id)
    {
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    return response()->json($user);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name'=>'required|string',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:6',
            'role_id'=>'required|exists:roles,id'
        ]);

        $user = User::create([
            'name'=>$v['name'],
            'email'=>$v['email'],
            'password'=>Hash::make($v['password']),
            'role_id'=>$v['role_id'],
            'is_temporary_password'=>true,
        ]);

        return response()->json(['message'=>'User created','user'=>$user],201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin()) {
            return response()->json(['error'=>'Super Admin cannot be updated'],403);
        }

        $v = $request->validate([
            'name'=>'sometimes|string',
            'email'=>['sometimes','email', Rule::unique('users')->ignore($user->id)],
            'role_id'=>'sometimes|exists:roles,id'
        ]);

        $user->update($v);

        return response()->json(['message'=>'User updated','user'=>$user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin()) {
            return response()->json(['error'=>'Super Admin cannot be deleted'],403);
        }

        $user->delete();
        return response()->json(['message'=>'User deleted']);
    }

    public function changePassword(Request $request)
{
    // Validate the request fields (these come from your frontend form)
    $request->validate([
        'old_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Check if the old password matches the hashed one in the DB
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json(['error' => 'Incorrect old password'], 422);
    }

    // Update the password
    $user->password = Hash::make($request->new_password);
    $user->is_temporary_password = false;
    $user->save();

    return response()->json(['message' => 'Password changed successfully']);
}

}