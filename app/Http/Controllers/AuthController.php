<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $request->user()->createToken('API Token')->plainTextToken;
            $projects = $user->projects;
            return response()->json(['token' => $token, 'projects' => $projects, 'id' => $user->id], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function updateUser(Request $request, $id)
{
    $request->validate([
        'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
        'password' => 'sometimes|string|min:8',
        'onesignal_id' => 'sometimes|string|max:255',
    ]);

    $user = User::findOrFail($id);

    if ($request->has('name')) {
        $user->name = $request->name;
    }

    if ($request->has('email')) {
        $user->email = $request->email;
    }

    if ($request->has('password')) {
        $user->password = Hash::make($request->password);
    }

    if ($request->has('onesignal_id')) {
        $user->onesignal_id = $request->onesignal_id;
    }

    $user->save();

    return response()->json(['user' => $user], 200);
}

    

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
