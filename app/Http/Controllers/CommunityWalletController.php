<?php

namespace App\Http\Controllers;

use App\Models\CommunityWallet;
use Illuminate\Http\Request;

class CommunityWalletController extends Controller
{
    public function index()
    {
        $wallets = CommunityWallet::latest()->get();
        return view('community.index', compact('wallets'));
    }

    public function create()
    {
        return view('community.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'        => 'required|string|max:120',
            'mode'        => 'required|in:A,B',
            'address'     => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/', 'unique:community_wallets,address'],
            'description' => 'nullable|string|max:500',
        ]);
        $data['address']    = strtolower($data['address']);
        $data['created_by'] = auth()->id();

        $w = CommunityWallet::create($data);
        return redirect('/community/' . $w->id)->with('success', 'Dompet komunitas terdaftar.');
    }

    public function show(int $id)
    {
        $wallet = CommunityWallet::findOrFail($id);
        return view('community.show', compact('wallet'));
    }
}
