<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        // Vérification du rôle admin (même si le milieu de terrain le fera)
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $users = User::all();
        return response()->json($users);
    }

    /**
     * Update the specified user role.
     */
    public function updateRole(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'role' => 'required|string|in:admin,organisateur,standard',
        ]);

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'message' => 'Rôle mis à jour avec succès.',
            'user' => $user
        ]);
    }
}
