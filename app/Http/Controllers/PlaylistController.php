<?php

namespace App\Http\Controllers;

use App\Models\Playlist;

class PlaylistController extends Controller
{
    public function index()
    {
        $playlists = Playlist::query()
            ->withCount('talks')
            ->latest('first_recording_date')
            ->get();

        return view('playlists.index', ['playlists' => $playlists]);
    }

    public function show(Playlist $playlist)
    {
        $playlist->load(['talks' => fn ($q) => $q->select('talks.id', 'title')]);

        return view('playlists.show', ['playlist' => $playlist]);
    }
}
