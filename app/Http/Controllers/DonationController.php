<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Disbursement;
use App\Support\Identity;
use App\Services\SepoliaVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DonationController extends Controller
{
    private function ensureSupervisor(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSupervisor(), 403, 'Hanya pengawas.');
    }

    private function configured(): bool
    {
        $pool = config('chain.donation_pool');
        return $pool && !preg_match('/^0x0+$/', strtolower($pool));
    }

    /** Daftar campaign donasi (publik) — grid card ala Kitabisa/Dompet Dhuafa. */
    public function index()
    {
        $configured = $this->configured();

        // Total masuk & total disalurkan per campaign (dari catatan terverifikasi).
        $raised    = Donation::selectRaw('campaign_id, SUM(amount) t')->groupBy('campaign_id')->pluck('t', 'campaign_id');
        $disbursed = Disbursement::selectRaw('campaign_id, SUM(amount) t')->groupBy('campaign_id')->pluck('t', 'campaign_id');

        $campaigns = Campaign::latest()->get()->map(function ($c) use ($raised, $disbursed) {
            $in  = (float) ($raised[$c->id] ?? 0);
            $out = (float) ($disbursed[$c->id] ?? 0);
            return [
                'model'     => $c,
                'raised'    => $in,                       // total masuk
                'disbursed' => $out,                      // sudah disalurkan
                'balance'   => max(0, $in - $out),        // saldo saat ini
                'recipient' => Identity::resolve($c->recipient_wallet),
            ];
        });

        return view('donate.index', compact('campaigns', 'configured'));
    }

    /** Form buat campaign (pengawas). */
    public function create()
    {
        $this->ensureSupervisor();
        return view('donate.create');
    }

    /** Simpan campaign baru (pengawas). */
    public function store(Request $req)
    {
        $this->ensureSupervisor();

        $data = $req->validate([
            'title'            => 'required|string|max:120',
            'description'      => 'nullable|string|max:2000',
            'recipient_wallet' => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'goal_amount'      => 'nullable|numeric|min:0',
            'closes_at'        => 'nullable|date|after:today',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $slug = Str::slug($data['title']) . '-' . Str::lower(Str::random(6));

        $imageName = null;
        if ($req->hasFile('image')) {
            $imageName = Str::uuid() . '.' . $req->file('image')->getClientOriginalExtension();
            $req->file('image')->move(public_path('campaign_images'), $imageName);
        }

        $c = Campaign::create([
            'title'            => $data['title'],
            'slug'             => $slug,
            'description'      => $data['description'] ?? null,
            'image'            => $imageName,
            'recipient_wallet' => strtolower($data['recipient_wallet']),
            'goal_amount'      => $data['goal_amount'] ?? null,
            'closes_at'        => $data['closes_at'] ?? null,
            'status'           => 'active',
            'created_by'       => auth()->id(),
        ]);

        return redirect('/donate/' . $c->slug)->with('success', 'Campaign donasi dibuat.');
    }

    /** Form edit campaign (pengawas). */
    public function edit(string $slug)
    {
        $this->ensureSupervisor();
        $campaign = Campaign::where('slug', $slug)->firstOrFail();
        return view('donate.edit', compact('campaign'));
    }

    /** Simpan perubahan campaign (pengawas). */
    public function update(Request $req, string $slug)
    {
        $this->ensureSupervisor();
        $campaign = Campaign::where('slug', $slug)->firstOrFail();

        $data = $req->validate([
            'title'            => 'required|string|max:120',
            'description'      => 'nullable|string|max:2000',
            'recipient_wallet' => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'goal_amount'      => 'nullable|numeric|min:0',
            'closes_at'        => 'nullable|date',
            'status'           => 'required|in:active,closed',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        if ($req->hasFile('image')) {
            if ($campaign->image) {
                @unlink(public_path('campaign_images/' . $campaign->image));
            }
            $imageName = Str::uuid() . '.' . $req->file('image')->getClientOriginalExtension();
            $req->file('image')->move(public_path('campaign_images'), $imageName);
            $campaign->image = $imageName;
        }

        $campaign->title            = $data['title'];
        $campaign->description      = $data['description'] ?? null;
        $campaign->recipient_wallet = strtolower($data['recipient_wallet']);
        $campaign->goal_amount      = $data['goal_amount'] ?? null;
        $campaign->closes_at        = $data['closes_at'] ?? null;
        $campaign->status           = $data['status'];
        $campaign->save();

        return redirect('/donate/' . $campaign->slug)->with('success', 'Campaign donasi diperbarui.');
    }

    /** Hapus campaign (pengawas). */
    public function destroy(string $slug)
    {
        $this->ensureSupervisor();
        $campaign = Campaign::where('slug', $slug)->firstOrFail();

        if ($campaign->image) {
            @unlink(public_path('campaign_images/' . $campaign->image));
        }
        // Lepas tautan donasi/penyaluran (catatan tetap ada, tapi tak menunjuk campaign terhapus).
        Donation::where('campaign_id', $campaign->id)->update(['campaign_id' => null]);
        Disbursement::where('campaign_id', $campaign->id)->update(['campaign_id' => null]);
        $campaign->delete();

        return redirect('/donate')->with('success', 'Campaign donasi dihapus.');
    }

    /** Detail campaign (publik) + form donasi + kartu penyalur (pengawas). */
    public function show(string $slug, SepoliaVerifier $verifier)
    {
        $campaign = Campaign::where('slug', $slug)->firstOrFail();

        $raised    = (float) Donation::where('campaign_id', $campaign->id)->sum('amount');
        $disbursed = (float) Disbursement::where('campaign_id', $campaign->id)->sum('amount');
        $balance   = max(0, $raised - $disbursed); // saldo saat ini = masuk - disalurkan (E2)
        $donors = (int) Donation::where('campaign_id', $campaign->id)->distinct('donor_wallet')->count('donor_wallet');

        $recent = Donation::where('campaign_id', $campaign->id)->latest()->limit(15)->get()->map(fn ($d) => [
            'identity' => Identity::resolve($d->donor_wallet),
            'wallet'   => $d->donor_wallet,
            'amount'   => $d->amount,
            'tx'       => $d->tx_hash,
            'at'       => $d->created_at,
        ]);

        $disbursements = Disbursement::where('campaign_id', $campaign->id)->latest()->get();

        // Saldo yang masih bisa disalurkan (belum dikirim ke penerima), live dari chain.
        $poolBalance = ($this->configured() && auth()->check() && auth()->user()->isSupervisor())
            ? $verifier->campaignBalanceOnChain($campaign->chainId())
            : null;

        return view('donate.show', compact('campaign', 'raised', 'disbursed', 'balance', 'donors', 'recent', 'disbursements', 'poolBalance') + [
            'configured' => $this->configured(),
        ]);
    }

    /** Catat donasi setelah verifikasi on-chain. Publik. */
    public function donate(Request $req, SepoliaVerifier $verifier)
    {
        $data = $req->validate([
            'slug'    => 'required|string',
            'tx_hash' => 'required|string|size:66',
        ]);

        $campaign = Campaign::where('slug', $data['slug'])->firstOrFail();

        if (Donation::where('tx_hash', $data['tx_hash'])->exists()) {
            return response()->json(['success' => true]);
        }

        $v = $verifier->verifyDonation($data['tx_hash'], $campaign->chainId());
        if (!$v['ok']) {
            return response()->json(['success' => false, 'message' => $v['reason']], 422);
        }

        try {
            Donation::create([
                'campaign_id'  => $campaign->id,
                'donor_wallet' => $v['donor'],
                'amount'       => $v['amount_tlkm'],
                'tx_hash'      => $data['tx_hash'],
                'block_number' => $v['block_number'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['success' => true]);
        }

        // Notifikasi ke pembuat campaign: donasi masuk.
        $amt = rtrim(rtrim(number_format((float) $v['amount_tlkm'], 6, '.', ''), '0'), '.');
        $donorName = \App\Support\Identity::resolve($v['donor'])['name'] ?? 'Seseorang';
        \App\Support\Notify::send($campaign->created_by, 'donation', 'Donasi masuk',
            "{$donorName} berdonasi {$amt} TLKM ke \"{$campaign->title}\".", '/donate/' . $campaign->slug, '💝');

        return response()->json(['success' => true, 'amount' => $v['amount_tlkm']]);
    }

    /** Catat penyaluran setelah verifikasi on-chain. Pengawas. */
    public function disburse(Request $req, SepoliaVerifier $verifier)
    {
        $this->ensureSupervisor();

        $data = $req->validate([
            'slug'    => 'required|string',
            'tx_hash' => 'required|string|size:66',
        ]);

        $campaign = Campaign::where('slug', $data['slug'])->firstOrFail();

        if (Disbursement::where('tx_hash', $data['tx_hash'])->exists()) {
            return response()->json(['success' => true]);
        }

        $v = $verifier->verifyDisbursement($data['tx_hash'], $campaign->chainId());
        if (!$v['ok']) {
            return response()->json(['success' => false, 'message' => $v['reason']], 422);
        }

        // Keamanan: alamat tujuan on-chain HARUS sama dengan penerima campaign.
        if ($v['to'] !== strtolower($campaign->recipient_wallet)) {
            return response()->json(['success' => false, 'message' => 'Alamat tujuan tidak cocok dengan penerima campaign.'], 422);
        }

        try {
            Disbursement::create([
                'campaign_id'  => $campaign->id,
                'to_address'   => $v['to'],
                'amount'       => $v['amount_tlkm'],
                'by_wallet'    => $v['by'],
                'tx_hash'      => $data['tx_hash'],
                'block_number' => $v['block_number'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true, 'amount' => $v['amount_tlkm']]);
    }
}
