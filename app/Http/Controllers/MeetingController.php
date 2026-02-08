<?php

// app/Http/Controllers/MeetingController.php
namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    // Liste des réunions
    public function index()
    {
        $user = Auth::user();

        // Pour un organisateur : ses réunions + celles où il participe
        if ($user->role === 'organisateur') {
            $meetings = Meeting::where('organizer_id', $user->id)
                ->orWhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['organizer', 'participants'])
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // Pour un utilisateur standard : réunions où il participe
            $meetings = $user->meetings()
                ->with(['organizer', 'participants'])
                ->orderBy('date', 'asc')
                ->get();
        }

        return response()->json($meetings);
    }

    // Créer une réunion
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
        ]);

        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'location' => $request->location,
            'organizer_id' => Auth::id(),
            'status' => 'à venir',
        ]);

        return response()->json($meeting, 201);
    }

    // Détails d'une réunion
    public function show(Meeting $meeting)
    {
        $meeting->load(['organizer', 'participants']);
        return response()->json($meeting);
    }

    // Mettre à jour une réunion
    public function update(Request $request, Meeting $meeting)
    {
        // Vérifier que l'utilisateur est l'organisateur
        if ($meeting->organizer_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'date' => 'date',
            'location' => 'string|max:255',
            'status' => 'in:à venir,en cours,terminée',
        ]);

        $meeting->update($request->only(['title', 'description', 'date', 'location', 'status']));

        return response()->json($meeting);
    }

    // Supprimer une réunion
    public function destroy(Meeting $meeting)
    {
        if ($meeting->organizer_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $meeting->delete();

        return response()->json(['message' => 'Réunion supprimée']);
    }

    // Rejoindre une réunion
    public function join(Meeting $meeting)
    {
        $user = Auth::user();

        if ($meeting->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Déjà inscrit']);
        }

        $meeting->participants()->attach($user->id);

        return response()->json(['message' => 'Inscription réussie']);
    }

    // Quitter une réunion
    public function leave(Meeting $meeting)
    {
        $user = Auth::user();
        $meeting->participants()->detach($user->id);

        return response()->json(['message' => 'Désinscription réussie']);
    }
}
