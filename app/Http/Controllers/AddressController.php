<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = ShippingAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')->latest()->get();

        return view('addresses.index', compact('addresses'));
    }

    public function setDefault(Request $req)
    {
        $addr = ShippingAddress::where('id', $req->id)->where('user_id', auth()->id())->firstOrFail();
        ShippingAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        $addr->update(['is_default' => true]);

        return back()->with('success', 'Alamat utama diperbarui.');
    }

    public function destroy(Request $req)
    {
        ShippingAddress::where('id', $req->id)->where('user_id', auth()->id())->delete();

        return back()->with('success', 'Alamat dihapus.');
    }
}
