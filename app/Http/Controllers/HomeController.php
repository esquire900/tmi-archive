<?php

namespace App\Http\Controllers;

use App\Models\Playlist;

class HomeController extends Controller
{
    public function index()
    {
        $playlists = Playlist::query()
            ->with(['talks' => fn ($q) => $q->select('talks.id', 'title')])
            ->latest('first_recording_date')
            ->limit(2)
            ->get();

        return view('home', [
            'playlists' => $playlists,
            'displayHeader' => true,
        ]);
    }

    public function contact()
    {
        return view('contact');
    }
}
