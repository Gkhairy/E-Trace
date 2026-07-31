<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $req)
    {
        $user = auth()->user();

        $data = $req->validate([
            'public_name'     => 'nullable|string|max:40',
            'explorer_public' => 'nullable|boolean',
        ]);

        // Nama publik = pseudonim opsional yang tampil di Explorer (TANPA badge
        // terverifikasi). Label resmi ("US GOV" dll) tetap wewenang pengawas &
        // selalu menang atas nama publik ini, jadi ini tak bisa dipakai menyamar.
        $user->public_name     = trim($data['public_name'] ?? '') ?: null;
        $user->explorer_public = $req->boolean('explorer_public');
        $user->save();

        return redirect('/profile')->with('success', 'Profil publik diperbarui.');
    }
}
