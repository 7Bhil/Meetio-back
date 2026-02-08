<?php

// app/Http/Controllers/MeetingController.php
namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Notifications\MeetingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    // Liste des réunions
    public function index(Request $request)
    {
        $user = Auth::user();

        // Si le paramètre 'discover' est présent, on montre tout ce qui est "à venir"
        if ($request->has('discover')) {
            $meetings = Meeting::where('status', 'à venir')
                ->where('date', '>', now())
                ->with(['organizer', 'participants'])
                ->orderBy('date', 'asc')
                ->get();
            return response()->json($meetings);
        }

        if ($user->role === 'admin') {
            // L'admin voit TOUTES les réunions
            $meetings = Meeting::with(['organizer', 'participants'])
                ->orderBy('date', 'asc')
                ->get();
        } elseif ($user->role === 'organisateur') {
            // L'organisateur voit ses propres réunions + celles où il est invité
            $meetings = Meeting::where('organizer_id', $user->id)
                ->orWhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['organizer', 'participants'])
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // Utilisateur standard : voit uniquement les réunions où il participe
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
            'duration' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'location' => 'required|string|max:255',
        ]);

        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'duration' => $request->duration ?? 60,
            'max_participants' => $request->max_participants,
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
            'duration' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'location' => 'string|max:255',
            'status' => 'in:à venir,en cours,terminée',
        ]);

        $meeting->update($request->only(['title', 'description', 'date', 'duration', 'max_participants', 'location', 'status']));

        // Notifier les participants
        foreach ($meeting->participants as $participant) {
            $participant->notify(new MeetingNotification(
                'update',
                'Réunion modifiée',
                'La réunion "' . $meeting->title . '" a été mise à jour.'
            ));
        }

        return response()->json($meeting);
    }

    // Supprimer une réunion
    public function destroy(Meeting $meeting)
    {
        if ($meeting->organizer_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        // Notifier les participants avant suppression
        foreach ($meeting->participants as $participant) {
            $participant->notify(new MeetingNotification(
                'delete',
                'Réunion annulée',
                'La réunion "' . $meeting->title . '" a été annulée.'
            ));
        }

        $meeting->delete();

        return response()->json(['message' => 'Réunion supprimée']);
    }

    // Rejoindre une réunion
    public function join(Meeting $meeting)
    {
        $user = Auth::user();

        if ($meeting->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Déjà inscrit'], 400);
        }

        // Vérifier les chevauchements
        $newStart = \Carbon\Carbon::parse($meeting->date);
        $newEnd = (clone $newStart)->addMinutes($meeting->duration);

        $overlaps = $user->meetings()
            ->where('status', 'à venir')
            ->get()
            ->filter(function ($m) use ($newStart, $newEnd) {
                $mStart = \Carbon\Carbon::parse($m->date);
                $mEnd = (clone $mStart)->addMinutes($m->duration);
                return ($newStart < $mEnd) && ($mStart < $newEnd);
            });

        if ($overlaps->isNotEmpty()) {
            return response()->json([
                'error' => 'Chevauchement détecté',
                'message' => 'Vous participez déjà à une réunion sur ce créneau : ' . $overlaps->first()->title
            ], 422);
        }

        // Vérifier la capacité maximale
        if ($meeting->max_participants && $meeting->participants()->count() >= $meeting->max_participants) {
            return response()->json([
                'error' => 'Réunion complète',
                'message' => 'Désolé, cette réunion a atteint sa capacité maximale de ' . $meeting->max_participants . ' personnes.'
            ], 422);
        }

        $meeting->participants()->attach($user->id);

        // Notifier l'organisateur
        $meeting->organizer->notify(new MeetingNotification(
            'join',
            'Nouveau participant',
            $user->name . ' a rejoint votre réunion : ' . $meeting->title
        ));

        return response()->json(['message' => 'Inscription réussie']);
    }

    // Quitter une réunion
    public function leave(Meeting $meeting)
    {
        $user = Auth::user();
        $meeting->participants()->detach($user->id);

        // Notifier l'organisateur
        $meeting->organizer->notify(new MeetingNotification(
            'leave',
            'Désistement',
            $user->name . ' a quitté votre réunion : ' . $meeting->title
        ));

        return response()->json(['message' => 'Désinscription réussie']);
    }
}
