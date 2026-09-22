<?php

namespace App\Http\Controllers;

use App\Models\CommunityWallet;
use App\Models\CommunityMember;
use App\Models\CommunityProposal;
use App\Models\CommunityApproval;
use App\Models\CommunityDeposit;
use App\Models\CommunityMemberNickname;
use App\Models\Friendship;
use App\Models\User;
use App\Services\EmbeddedWallet;
use App\Services\ChainSigner;
use App\Services\ChainVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Dompet komunitas DIKELOLA APP (custodial, prototipe/testnet):
 * kunci dompet dienkripsi dgn secret server & ditandatangani backend. Anggota
 * mengonfirmasi aksi dengan PIN masing-masing; aturan (jatah A / ambang B) di DB.
 */
class CommunityWalletController extends Controller
{
    private const ERC20_ABI = [
        ['inputs' => [['name' => 'to', 'type' => 'address'], ['name' => 'v', 'type' => 'uint256']], 'name' => 'transfer', 'outputs' => [['type' => 'bool']], 'type' => 'function'],
    ];

    public function index()
    {
        $ids = CommunityMember::where('user_id', auth()->id())->pluck('community_wallet_id');
        $wallets = CommunityWallet::whereIn('id', $ids)->orWhere('created_by', auth()->id())->latest()->get();
        return view('community.index', compact('wallets'));
    }

    public function create()
    {
        $friends = Friendship::where('user_id', auth()->id())->where('status', 'accepted')->with('friend')->get()
            ->map(fn ($f) => ['id' => $f->friend_id, 'name' => $f->friend->public_name ?: $f->friend->name])
            ->filter(fn ($f) => $f['name']);
        return view('community.create', compact('friends'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'          => 'required|string|max:120',
            'mode'          => 'required|in:A,B',
            'description'   => 'nullable|string|max:500',
            'members'       => 'nullable|array',       // id teman yang diundang
            'members.*'     => 'integer',
            'signers'       => 'nullable|array',       // id anggota penanda tangan wajib (Mode B)
            'signers.*'     => 'integer',
            'monthly_limit' => 'nullable|numeric|min:0', // Mode A
            'threshold'     => 'nullable|integer|min:1',  // Mode B
        ]);

        // Buat wallet komunitas (dikelola app) — kunci dienkripsi SECRET SERVER.
        $ew  = new EmbeddedWallet();
        $w   = $ew->generate();
        $enc = $ew->encryptServer($w['private']);

        // Anggota = pembuat + teman terpilih (yang benar-benar teman kita).
        $memberIds = collect($data['members'] ?? [])
            ->filter(fn ($id) => Friendship::where('user_id', auth()->id())->where('friend_id', $id)->where('status', 'accepted')->exists())
            ->push(auth()->id())->unique()->values();

        // Penanda tangan wajib (Mode B): pilihan dari anggota; default = SEMUA anggota.
        $signerIds = $data['mode'] === 'B'
            ? collect($data['signers'] ?? [])->filter(fn ($id) => $memberIds->contains($id))->unique()->values()
            : collect();
        if ($data['mode'] === 'B' && $signerIds->isEmpty()) {
            $signerIds = $memberIds; // fallback: semua anggota jadi penanda tangan
        }

        // Ambang M dibatasi jumlah PENANDA TANGAN (bukan seluruh anggota).
        $threshold = $data['mode'] === 'B'
            ? max(1, min((int) ($data['threshold'] ?? 2), $signerIds->count()))
            : null;

        $wallet = CommunityWallet::create([
            'name'        => $data['name'],
            'mode'        => $data['mode'],
            'managed'     => true,
            'address'     => $w['address'],
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
            'owner_id'    => auth()->id(), // pembuat = pemilik awal
            // Semua aksi BULAT: ambang = jumlah penanda tangan.
            'threshold'   => $data['mode'] === 'B' ? $signerIds->count() : null,
        ] + $enc);

        foreach ($memberIds as $uid) {
            CommunityMember::create([
                'community_wallet_id' => $wallet->id,
                'user_id'             => $uid,
                'is_signer'           => $data['mode'] === 'B' ? $signerIds->contains($uid) : false,
                'monthly_limit'       => $data['mode'] === 'A' ? ($data['monthly_limit'] ?? 0) : null,
                'spent'               => 0,
                'period_start'        => now(),
            ]);
        }

        // Notifikasi ke anggota yang diundang (selain pembuat).
        $inviter = auth()->user();
        $inviterName = $inviter->public_name ?: $inviter->name;
        foreach ($memberIds as $uid) {
            if ($uid !== auth()->id()) {
                \App\Support\Notify::send($uid, 'community', 'Diundang ke dompet komunitas',
                    "{$inviterName} mengundangmu ke \"{$wallet->name}\".", '/community/' . $wallet->id, '👥');
            }
        }

        // Gas drip untuk wallet komunitas (best-effort) supaya bisa menyalurkan dana.
        $this->gasDrip($wallet);

        return redirect('/community/' . $wallet->id)->with('success', 'Dompet komunitas dibuat.');
    }

    public function show(int $id, ChainVerifier $verifier, ChainSigner $signer)
    {
        $wallet = CommunityWallet::with(['members.user', 'proposals'])->findOrFail($id);
        $this->authorizeMember($wallet);

        $balance = $verifier->tlkmBalance($wallet->address);
        $gasEth  = $signer->ethBalance($wallet->address); // ETH untuk biaya gas
        $me = $wallet->members->firstWhere('user_id', auth()->id());

        // Penanda tangan wajib (Mode B) untuk menghitung persetujuan yang sah.
        $signerIds = $wallet->members->where('is_signer', true)->pluck('user_id');

        // Nickname PRIBADI: julukan yang KAMU (penatap) beri ke anggota lain di dompet
        // ini — hanya kamu yang melihatnya. Map: target_user_id => nickname.
        $nickMap = CommunityMemberNickname::where('community_wallet_id', $wallet->id)
            ->where('viewer_id', auth()->id())->pluck('nickname', 'target_user_id');
        $nameFor = fn ($uid, $real) => $nickMap[$uid] ?? $real;

        // Sisa jatah (Mode A) dgn reset 30 hari.
        $members = $wallet->members->map(function ($m) use ($wallet, $nickMap) {
            $remaining = null;
            if ($wallet->mode === 'A') {
                $spent = ($m->period_start && now()->greaterThanOrEqualTo($m->period_start->copy()->addDays(30))) ? 0 : (float) $m->spent;
                $remaining = max(0, (float) $m->monthly_limit - $spent);
            }
            $real = $m->user->public_name ?: $m->user->name;
            $nick = $nickMap[$m->user_id] ?? null;
            return ['user_id' => $m->user_id, 'name' => $nick ?: $real, 'real_name' => $real, 'nickname' => $nick,
                    'wallet' => $m->user->wallet_address,
                    'limit' => (float) $m->monthly_limit, 'remaining' => $remaining, 'is_me' => $m->user_id === auth()->id(),
                    'is_signer' => (bool) $m->is_signer, 'is_owner' => $wallet->isOwner($m->user_id)];
        });

        $required = $wallet->requiredApprovals();

        // Struk belanja: ambil foto produk sekali saja (hindari N+1) supaya penanda tangan
        // bisa melihat barang apa yang akan dibayar dari kas sebelum menyetujui.
        $pids = collect($wallet->proposals)->flatMap(fn ($p) => collect($p->meta['lines'] ?? [])->pluck('db_product_id'))->filter()->unique();
        $thumbs = $pids->isEmpty() ? collect() : \App\Models\Product::whereIn('id', $pids)->get()->mapWithKeys(fn ($pr) => [$pr->id => $pr->thumbnail()]);
        $receiptFor = function ($p) use ($thumbs, $nameFor) {
            $m = $p->meta ?? [];
            if (($p->type ?? null) !== 'purchase' || empty($m['lines'])) {
                return null;
            }
            $prop = $p->proposer_id ? \App\Models\User::find($p->proposer_id) : null;
            return [
                'order_id' => $m['order_id'] ?? '-',
                'at'       => optional($p->created_at)->translatedFormat('d M Y, H:i'),
                'by'       => $prop ? $nameFor($prop->id, $prop->public_name ?: $prop->name) : '-',
                'items'    => collect($m['lines'])->map(fn ($l) => [
                    'name'   => $l['name'] ?? 'Produk',
                    'qty'    => (int) ($l['qty'] ?? 1),
                    'amount' => (float) ($l['amount'] ?? 0),
                    'img'    => $thumbs[$l['db_product_id'] ?? null] ?? null,
                ])->values()->all(),
                'subtotal' => (float) ($m['total'] ?? 0),
                'shipping' => (float) ($m['shipping_tlkm'] ?? 0),
            ];
        };

        $proposals = $wallet->proposals->load('targetUser')->map(fn ($p) => [
            'receipt' => $receiptFor($p),
            'id' => $p->id, 'type' => $p->type ?: 'transfer',
            'to_wallet' => $p->to_wallet, 'to_name' => $p->to_name,
            'target_name' => $p->targetUser ? $nameFor($p->target_user_id, $p->targetUser->public_name ?: $p->targetUser->name) : null,
            'as_signer' => (bool) ($p->meta['as_signer'] ?? false),
            'amount' => (float) $p->amount, 'note' => $p->note, 'status' => $p->status, 'tx' => $p->tx_hash,
            'items' => is_array($p->meta['lines'] ?? null) ? count($p->meta['lines']) : 0,
            'required' => $required,
            'approvals' => $p->approvals()->whereIn('user_id', $signerIds)->count(),
            'approved_by_me' => $p->approvals()->where('user_id', auth()->id())->exists(),
        ]);

        // Riwayat setoran (mutasi): siapa menyetor, berapa, kapan (pakai nickname pribadi).
        $deposits = $wallet->deposits->load('user')->map(fn ($d) => [
            'name'   => $d->user ? $nameFor($d->user_id, $d->user->public_name ?: $d->user->name) : 'Eksternal',
            'wallet' => $d->from_wallet,
            'amount' => (float) $d->amount,
            'tx'     => $d->tx_hash,
            'at'     => $d->created_at,
        ]);

        // Apakah user ini penanda tangan wajib? Pemilik dompet?
        $iAmSigner = $me ? (bool) $me->is_signer : false;
        $iAmOwner  = $wallet->isOwner(auth()->id());

        // Teman pemilik yang BELUM jadi anggota — kandidat untuk diundang.
        $inviteCandidates = collect();
        if ($iAmOwner && $wallet->mode === 'B') {
            $memberIds = $wallet->members->pluck('user_id');
            $inviteCandidates = Friendship::where('user_id', auth()->id())->where('status', 'accepted')->with('friend')->get()
                ->map(fn ($f) => ['id' => $f->friend_id, 'name' => $f->friend->public_name ?: $f->friend->name])
                ->filter(fn ($c) => $c['name'] && !$memberIds->contains($c['id']))
                ->values();
        }

        return view('community.show', compact('wallet', 'balance', 'gasEth', 'members', 'proposals', 'deposits', 'me', 'iAmSigner', 'iAmOwner', 'signerIds', 'required', 'inviteCandidates'));
    }

    /** Mode A: tarik dana sampai jatah. Konfirmasi PIN. */
    public function withdraw(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['id' => 'required|integer', 'amount' => 'required|numeric|min:0.000001', 'pin' => 'required|digits:6']);
        $wallet = CommunityWallet::findOrFail($data['id']);
        abort_unless($wallet->mode === 'A', 422, 'Bukan mode jatah bulanan.');
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        $this->requirePin($data['pin']);

        // Reset periode bila lewat 30 hari.
        if (!$member->period_start || now()->greaterThanOrEqualTo($member->period_start->copy()->addDays(30))) {
            $member->period_start = now(); $member->spent = 0;
        }
        if (bccomp(bcadd((string) $member->spent, (string) $data['amount'], 6), (string) ($member->monthly_limit ?? 0), 6) > 0) {
            return response()->json(['success' => false, 'message' => 'Melebihi jatah bulan ini.'], 422);
        }

        $hash = $this->communitySign($wallet, $signer, auth()->user()->wallet_address, $data['amount']);
        $member->spent = bcadd((string) $member->spent, (string) $data['amount'], 6);
        $member->save();

        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Mode B: usulkan pengiriman dana (penerima via No HP/wallet). BULAT: butuh semua signer. */
    public function propose(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['id' => 'required|integer', 'to' => 'required|string', 'amount' => 'required|numeric|min:0.000001', 'note' => 'nullable|string|max:120', 'pin' => 'required|digits:6']);
        $wallet = CommunityWallet::findOrFail($data['id']);
        abort_unless($wallet->mode === 'B', 422, 'Bukan mode multisig.');
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        $this->requirePin($data['pin']);

        [$toWallet, $toName] = $this->resolveRecipient($data['to']);
        abort_unless($toWallet, 422, 'Penerima tidak ditemukan.');

        $p = CommunityProposal::create([
            'community_wallet_id' => $wallet->id, 'proposer_id' => auth()->id(), 'type' => 'transfer',
            'to_wallet' => $toWallet, 'to_name' => $toName, 'amount' => $data['amount'], 'note' => $data['note'] ?? null,
        ]);
        $this->autoApprove($p, $member);
        $amt = $this->fmtAmt($data['amount']);
        $this->notifyOtherSigners($wallet, 'Usulan butuh persetujuan',
            $this->meName() . " mengusulkan kirim {$amt} TLKM dari \"{$wallet->name}\". Butuh persetujuan semua penanda tangan.", '🗳️');

        return response()->json(['success' => true] + $this->maybeExecute($p, $wallet, $signer));
    }

    /**
     * Checkout: usulkan BELANJA memakai dana komunitas (Mode B).
     * Isi keranjang dikunci jadi snapshot di server (harga/penjual tak bisa diubah dari
     * browser). Bila semua penanda tangan setuju, dompet komunitas membayar escrow.
     */
    public function proposePurchase(Request $req, ChainSigner $signer, ChainVerifier $verifier, \App\Services\ShippingService $shipping)
    {
        $data = $req->validate([
            'id'                  => 'required|integer',
            'shipping_address_id' => 'nullable|integer',
            'shipping'            => 'nullable|array',
            'address_label'       => 'nullable|string|max:60',
            'is_insured'          => 'nullable|boolean',
            'note'                => 'nullable|string|max:120',
            'pin'                 => 'required|digits:6',
        ]);

        $wallet = CommunityWallet::findOrFail($data['id']);
        abort_unless($wallet->mode === 'B', 422, 'Dompet ini bukan mode multisig.');
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        $this->requirePin($data['pin']);

        // Alamat: pakai yang tersimpan bila dipilih & miliknya; kalau tidak, simpan input baru.
        $addr = null;
        if (!empty($data['shipping_address_id'])) {
            $addr = \App\Models\ShippingAddress::where('id', $data['shipping_address_id'])
                ->where('user_id', auth()->id())->first();
        }
        if (!$addr) {
            $s = $data['shipping'] ?? [];
            abort_if(empty($s['address']) || empty($s['recipient_name']), 422, 'Alamat pengiriman belum lengkap.');
            $addr = \App\Models\ShippingAddress::create([
                'user_id'        => auth()->id(),
                'label'          => $data['address_label'] ?? null,
                'recipient_name' => $s['recipient_name'] ?? '',
                'phone'          => $s['phone'] ?? '',
                'address'        => $s['address'] ?? '',
                'city'           => $s['city'] ?? '',
                'postal_code'    => $s['postal_code'] ?? '',
                'notes'          => $s['notes'] ?? null,
            ]);
        }

        // Snapshot keranjang (otoritatif dari server).
        $snap = app(\App\Services\CommunityPurchase::class)
            ->snapshot(auth()->id(), $addr, (bool) ($data['is_insured'] ?? false), $shipping);

        // Saldo kas komunitas harus cukup untuk total produk.
        $balance = $verifier->tlkmBalance($wallet->address);
        if ($balance !== null && bccomp($balance, (string) $snap['total'], 6) < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo dompet komunitas (' . $this->fmtAmt($balance) . ' TLKM) kurang dari total belanja (' . $this->fmtAmt($snap['total']) . ' TLKM).',
            ], 422);
        }

        $count = count($snap['lines']);
        $p = CommunityProposal::create([
            'community_wallet_id' => $wallet->id,
            'proposer_id'         => auth()->id(),
            'type'                => 'purchase',
            'to_wallet'           => strtolower((string) config('chain.gateway')),
            'to_name'             => 'Escrow marketplace',
            'amount'              => $snap['total'],
            'note'                => $data['note'] ?? ($count . ' barang · order ' . $snap['order_id']),
            'meta'                => $snap,
        ]);
        $this->autoApprove($p, $member);

        $amt = $this->fmtAmt($snap['total']);
        $this->notifyOtherSigners($wallet, 'Usulan belanja butuh persetujuan',
            $this->meName() . " ingin belanja {$count} barang senilai {$amt} TLKM memakai dana \"{$wallet->name}\". Butuh persetujuan semua penanda tangan.", '🛒');

        return response()->json([
            'success'     => true,
            'proposal_id' => $p->id,
            'wallet_id'   => $wallet->id,
        ] + $this->maybeExecute($p, $wallet, $signer));
    }

    /**
     * Konfirmasi terima / sengketa untuk order yang dibayar DANA KOMUNITAS.
     * Pembeli on-chain = dompet komunitas, sehingga confirmItem/disputeItem wajib
     * ditandatangani kunci dompet komunitas. Pemicunya HANYA pembeli (pemilik order).
     */
    public function orderAction(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'order_id'   => 'required|string|max:80',
            'item_index' => 'required|integer|min:0',
            'action'     => 'required|in:confirm,dispute,refund',
            'pin'        => 'required|digits:6',
        ]);

        $order = \App\Models\Order::where('order_id', $data['order_id'])->firstOrFail();
        abort_unless($order->community_wallet_id, 422, 'Order ini bukan pembelian dana komunitas.');
        abort_unless((int) $order->user_id === (int) auth()->id(), 403, 'Hanya pembeli yang boleh melakukan ini.');
        $this->requirePin($data['pin']);

        $item = \App\Models\OrderItem::where('order_ref_id', $order->id)
            ->where('item_index', (int) $data['item_index'])->firstOrFail();
        abort_unless($item->status === 'paid', 422, 'Item tidak dalam status dibayar.');

        $wallet = CommunityWallet::findOrFail($order->community_wallet_id);
        $this->ensureGas($wallet);
        $priv = (new EmbeddedWallet())->decryptServer($wallet->only(['wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag']));
        abort_unless($priv, 500, 'Kunci dompet komunitas gagal dibuka.');

        $method = match ($data['action']) {
            'confirm' => 'confirmItem',
            'refund'  => 'refundItem',
            default   => 'disputeItem',
        };
        $abi = [[
            'inputs' => [['name' => 'orderId', 'type' => 'string'], ['name' => 'index', 'type' => 'uint256']],
            'name' => $method, 'outputs' => [], 'type' => 'function',
        ]];
        $hash = $signer->sendContractCall($priv, config('chain.gateway'), $abi, $method, [$order->order_id, (int) $data['item_index']]);

        if ($data['action'] === 'confirm') {
            $item->status = 'completed';
            $item->fulfillment_status = 'delivered';
            $item->delivered_at = $item->delivered_at ?? now();
        } elseif ($data['action'] === 'refund') {
            $item->status = 'refunded'; // dana kembali ke KAS KOMUNITAS (pembeli on-chain)
        } else {
            $item->status = 'disputed';
        }
        $item->save();

        // Header order selesai bila tak ada lagi item yang menunggu.
        if (!\App\Models\OrderItem::where('order_ref_id', $order->id)
            ->whereIn('status', ['paid', 'disputed', 'pending_confirmation'])->exists()) {
            $order->status = 'completed';
            $order->save();
        }

        [$title, $body, $icon] = match ($data['action']) {
            'confirm' => ['Barang komunitas diterima', ' mengonfirmasi penerimaan barang — dana dilepas ke penjual.', '📦'],
            'refund'  => ['Dana komunitas dikembalikan', ' mengajukan refund — dana kembali ke kas komunitas.', '↩️'],
            default   => ['Sengketa dibuka', ' membuka sengketa untuk belanja komunitas.', '⚖️'],
        };
        $this->notifyAllMembers($wallet, $title, $this->meName() . $body, $icon);

        return response()->json(['success' => true, 'tx_hash' => $hash]);
    }

    /** Owner: usulkan UNDANG (add) / KICK (remove) anggota. BULAT: butuh semua signer. */
    public function memberPropose(Request $req, ChainSigner $signer)
    {
        $data = $req->validate([
            'id' => 'required|integer', 'action' => 'required|in:add,remove',
            'target_id' => 'required|integer', 'as_signer' => 'nullable|boolean', 'pin' => 'required|digits:6',
        ]);
        $wallet = CommunityWallet::findOrFail($data['id']);
        abort_unless($wallet->mode === 'B', 422, 'Hanya untuk dompet multisig.');
        abort_unless($wallet->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa mengundang / mengeluarkan anggota.');
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        $this->requirePin($data['pin']);

        $targetId = (int) $data['target_id'];
        $meta = null;
        if ($data['action'] === 'add') {
            abort_unless(Friendship::where('user_id', auth()->id())->where('friend_id', $targetId)->where('status', 'accepted')->exists(), 422, 'Hanya bisa mengundang teman.');
            abort_if(CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', $targetId)->exists(), 422, 'Orang ini sudah jadi anggota.');
            $meta = ['as_signer' => (bool) ($data['as_signer'] ?? false)];
        } else {
            abort_if($targetId === $wallet->ownerId(), 422, 'Pemilik tidak bisa dikeluarkan. Transfer kepemilikan dulu.');
            abort_unless(CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', $targetId)->exists(), 422, 'Bukan anggota dompet ini.');
        }
        $target = User::find($targetId);
        $targetName = $target ? ($target->public_name ?: $target->name) : 'Pengguna';

        $p = CommunityProposal::create([
            'community_wallet_id' => $wallet->id, 'proposer_id' => auth()->id(),
            'type' => $data['action'] === 'add' ? 'add_member' : 'remove_member',
            'to_wallet' => '', 'amount' => 0, 'target_user_id' => $targetId, 'to_name' => $targetName, 'meta' => $meta,
        ]);
        $this->autoApprove($p, $member);
        $verb = $data['action'] === 'add' ? 'mengundang' : 'mengeluarkan';
        $this->notifyOtherSigners($wallet, 'Usulan anggota butuh persetujuan',
            $this->meName() . " ingin {$verb} \"{$targetName}\" di \"{$wallet->name}\". Butuh persetujuan semua penanda tangan.", '👥');

        return response()->json(['success' => true] + $this->maybeExecute($p, $wallet, $signer));
    }

    /** Owner: usulkan TRANSFER KEPEMILIKAN ke signer lain. BULAT: butuh semua signer. */
    public function ownerPropose(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['id' => 'required|integer', 'target_id' => 'required|integer', 'pin' => 'required|digits:6']);
        $wallet = CommunityWallet::findOrFail($data['id']);
        abort_unless($wallet->mode === 'B', 422, 'Hanya untuk dompet multisig.');
        abort_unless($wallet->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa memindahkan kepemilikan.');
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        $this->requirePin($data['pin']);

        $targetId = (int) $data['target_id'];
        abort_if($targetId === $wallet->ownerId(), 422, 'Sudah menjadi pemilik.');
        abort_unless(CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', $targetId)->where('is_signer', true)->exists(), 422, 'Pemilik baru harus salah satu penanda tangan.');
        $target = User::find($targetId);
        $targetName = $target ? ($target->public_name ?: $target->name) : 'Pengguna';

        $p = CommunityProposal::create([
            'community_wallet_id' => $wallet->id, 'proposer_id' => auth()->id(), 'type' => 'transfer_ownership',
            'to_wallet' => '', 'amount' => 0, 'target_user_id' => $targetId, 'to_name' => $targetName,
        ]);
        $this->autoApprove($p, $member);
        $this->notifyOtherSigners($wallet, 'Usulan transfer kepemilikan',
            $this->meName() . " ingin memindahkan kepemilikan \"{$wallet->name}\" ke \"{$targetName}\". Butuh persetujuan semua penanda tangan.", '🔑');

        return response()->json(['success' => true] + $this->maybeExecute($p, $wallet, $signer));
    }

    /** Mode B: setujui usulan apa pun (PIN). Bila semua signer setuju → eksekusi. */
    public function approve(Request $req, ChainSigner $signer)
    {
        $data = $req->validate(['proposal_id' => 'required|integer', 'pin' => 'required|digits:6']);
        $p = CommunityProposal::findOrFail($data['proposal_id']);
        $wallet = CommunityWallet::findOrFail($p->community_wallet_id);
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        abort_unless($p->status === 'open', 422, 'Usulan sudah selesai.');
        abort_unless($member->is_signer, 403, 'Kamu bukan penanda tangan yang ditunjuk untuk dompet ini.');
        $this->requirePin($data['pin']);

        CommunityApproval::firstOrCreate(['proposal_id' => $p->id, 'user_id' => auth()->id()]);
        return response()->json(['success' => true] + $this->maybeExecute($p, $wallet, $signer));
    }

    /** Mode B: TOLAK usulan (PIN). Karena persetujuan bulat, 1 penolakan membatalkan usulan. */
    public function reject(Request $req)
    {
        $data = $req->validate(['proposal_id' => 'required|integer', 'pin' => 'required|digits:6']);
        $p = CommunityProposal::findOrFail($data['proposal_id']);
        $wallet = CommunityWallet::findOrFail($p->community_wallet_id);
        $member = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->firstOrFail();
        abort_unless($p->status === 'open', 422, 'Usulan sudah selesai.');
        abort_unless($member->is_signer, 403, 'Kamu bukan penanda tangan yang ditunjuk untuk dompet ini.');
        $this->requirePin($data['pin']);

        $p->update(['status' => 'rejected']);
        $this->notifyAllMembers($wallet, 'Usulan ditolak', $this->meName() . " menolak sebuah usulan di \"{$wallet->name}\".", '❌');
        return response()->json(['success' => true, 'rejected' => true]);
    }

    /** Setel nickname PRIBADI untuk seorang ANGGOTA — hanya kamu (penatap) yang melihatnya. */
    public function setMemberNickname(Request $req)
    {
        $data = $req->validate(['id' => 'required|integer', 'target_id' => 'required|integer', 'nickname' => 'nullable|string|max:60']);
        $wallet = CommunityWallet::findOrFail($data['id']);
        $this->authorizeMember($wallet); // penatap harus anggota
        $targetId = (int) $data['target_id'];
        abort_if($targetId === auth()->id(), 422, 'Tidak perlu nickname untuk diri sendiri.');
        abort_unless(CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', $targetId)->exists(), 422, 'Bukan anggota dompet ini.');

        $nick = trim((string) $data['nickname']);
        $keys = ['community_wallet_id' => $wallet->id, 'viewer_id' => auth()->id(), 'target_user_id' => $targetId];
        if ($nick === '') {
            CommunityMemberNickname::where($keys)->delete(); // kosong = hapus (pakai nama asli)
        } else {
            CommunityMemberNickname::updateOrCreate($keys, ['nickname' => $nick]);
        }
        return response()->json(['success' => true]);
    }

    /** Catat setoran (mutasi) setelah transfer TLKM ke kas berhasil. */
    public function recordDeposit(Request $req)
    {
        $data = $req->validate(['id' => 'required|integer', 'amount' => 'required|numeric|min:0.000001', 'tx_hash' => 'nullable|string|max:66']);
        $wallet = CommunityWallet::findOrFail($data['id']);
        $this->authorizeMember($wallet);
        CommunityDeposit::create([
            'community_wallet_id' => $wallet->id,
            'user_id'             => auth()->id(),
            'from_wallet'         => auth()->user()->wallet_address,
            'amount'              => $data['amount'],
            'tx_hash'             => $data['tx_hash'] ?? null,
        ]);
        return response()->json(['success' => true]);
    }

    // ===== helpers =====

    /** Pengusul auto-setuju HANYA bila ia penanda tangan. */
    private function autoApprove(CommunityProposal $p, CommunityMember $member): void
    {
        if ($member->is_signer) {
            CommunityApproval::firstOrCreate(['proposal_id' => $p->id, 'user_id' => auth()->id()]);
        }
    }

    /** Eksekusi usulan bila SEMUA penanda tangan sudah setuju (bulat). */
    private function maybeExecute(CommunityProposal $p, CommunityWallet $wallet, ChainSigner $signer): array
    {
        if ($p->status !== 'open') {
            return ['executed' => false];
        }
        $signerIds = CommunityMember::where('community_wallet_id', $wallet->id)->where('is_signer', true)->pluck('user_id');
        $count = $p->approvals()->whereIn('user_id', $signerIds)->count();
        if ($count < $wallet->requiredApprovals()) {
            return ['executed' => false, 'approvals' => $count, 'required' => $wallet->requiredApprovals()];
        }
        return $this->executeProposal($p, $wallet, $signer);
    }

    /** Jalankan aksi sesuai jenis usulan setelah persetujuan bulat tercapai. */
    private function executeProposal(CommunityProposal $p, CommunityWallet $wallet, ChainSigner $signer): array
    {
        switch ($p->type ?: 'transfer') {
            case 'add_member':
                CommunityMember::firstOrCreate(
                    ['community_wallet_id' => $wallet->id, 'user_id' => $p->target_user_id],
                    ['is_signer' => (bool) ($p->meta['as_signer'] ?? false), 'monthly_limit' => null, 'spent' => 0, 'period_start' => now()]
                );
                $this->syncThreshold($wallet);
                $p->update(['status' => 'executed']);
                $this->notifyAllMembers($wallet, 'Anggota baru ditambahkan', "\"{$p->to_name}\" ditambahkan ke \"{$wallet->name}\".", '👥');
                \App\Support\Notify::send((int) $p->target_user_id, 'community', 'Kamu ditambahkan ke dompet komunitas',
                    "Kamu kini anggota \"{$wallet->name}\".", '/community/' . $wallet->id, '👥');
                return ['executed' => true];

            case 'remove_member':
                CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', $p->target_user_id)->delete();
                $this->syncThreshold($wallet);
                $p->update(['status' => 'executed']);
                $this->notifyAllMembers($wallet, 'Anggota dikeluarkan', "\"{$p->to_name}\" dikeluarkan dari \"{$wallet->name}\".", '👋');
                \App\Support\Notify::send((int) $p->target_user_id, 'community', 'Kamu dikeluarkan dari dompet komunitas',
                    "Kamu dikeluarkan dari \"{$wallet->name}\".", '/community', '👋');
                return ['executed' => true];

            case 'transfer_ownership':
                $wallet->update(['owner_id' => $p->target_user_id]);
                $p->update(['status' => 'executed']);
                $this->notifyAllMembers($wallet, 'Kepemilikan dipindahkan', "Kepemilikan \"{$wallet->name}\" dipindahkan ke \"{$p->to_name}\".", '🔑');
                return ['executed' => true];

            case 'purchase':
                // Belanja pakai dana komunitas: dompet komunitas membayar escrow (approve +
                // payCart), lalu order dicatat atas nama pengusul sebagai pembeli.
                $this->ensureGas($wallet);
                $hash = app(\App\Services\CommunityPurchase::class)->payAndRecord($wallet, $p, $signer);
                $p->update(['status' => 'executed', 'tx_hash' => $hash]);
                $amt = $this->fmtAmt($p->amount);
                $this->notifyAllMembers($wallet, 'Belanja komunitas dibayar',
                    "Usulan disetujui — {$amt} TLKM dibayarkan ke escrow dari \"{$wallet->name}\".", '🛒');
                \App\Support\Notify::send((int) $p->proposer_id, 'order', 'Belanja komunitas disetujui',
                    'Pesananmu sudah dibayar dari dana komunitas dan ditahan escrow.', '/orders', '✅');
                return ['executed' => true, 'tx_hash' => $hash];

            default: // transfer dana
                $hash = $this->communitySign($wallet, $signer, $p->to_wallet, $p->amount);
                $p->update(['status' => 'executed', 'tx_hash' => $hash]);
                $amt = $this->fmtAmt($p->amount);
                $this->notifyAllMembers($wallet, 'Dana komunitas dikirim', "Usulan disetujui — {$amt} TLKM dikirim dari \"{$wallet->name}\".", '✅');
                return ['executed' => true, 'tx_hash' => $hash];
        }
    }

    /** Samakan ambang tersimpan dengan jumlah penanda tangan (bulat). */
    private function syncThreshold(CommunityWallet $wallet): void
    {
        $n = $wallet->members()->where('is_signer', true)->count();
        $wallet->update(['threshold' => max(1, $n)]);
    }

    private function meName(): string
    {
        return auth()->user()->public_name ?: auth()->user()->name;
    }

    private function fmtAmt(string|float $n): string
    {
        return rtrim(rtrim(number_format((float) $n, 6, '.', ''), '0'), '.');
    }

    private function notifyOtherSigners(CommunityWallet $wallet, string $title, string $body, string $icon): void
    {
        $others = CommunityMember::where('community_wallet_id', $wallet->id)->where('is_signer', true)->where('user_id', '!=', auth()->id())->pluck('user_id');
        foreach ($others as $uid) {
            \App\Support\Notify::send($uid, 'community', $title, $body, '/community/' . $wallet->id, $icon);
        }
    }

    private function notifyAllMembers(CommunityWallet $wallet, string $title, string $body, string $icon): void
    {
        foreach (CommunityMember::where('community_wallet_id', $wallet->id)->pluck('user_id') as $uid) {
            \App\Support\Notify::send((int) $uid, 'community', $title, $body, '/community/' . $wallet->id, $icon);
        }
    }

    // ===== authorization / signing helpers =====
    private function authorizeMember(CommunityWallet $wallet): void
    {
        $isMember = CommunityMember::where('community_wallet_id', $wallet->id)->where('user_id', auth()->id())->exists();
        abort_unless($isMember || $wallet->created_by === auth()->id(), 403, 'Bukan anggota dompet ini.');
    }

    private function requirePin(string $pin): void
    {
        $user = auth()->user();
        abort_unless($user->pin_hash, 422, 'Akun belum punya PIN.');
        abort_if($user->pinLocked(), 423, 'PIN terkunci sementara.');
        if (!Hash::check($pin, $user->pin_hash)) {
            $user->increment('pin_attempts');
            if ($user->pin_attempts >= 5) { $user->forceFill(['pin_locked_until' => now()->addMinutes(15), 'pin_attempts' => 0])->save(); abort(423, 'PIN salah 5×. Dikunci 15 menit.'); }
            abort(422, 'PIN salah. Sisa percobaan: ' . max(0, 5 - $user->pin_attempts) . '.');
        }
        $user->forceFill(['pin_attempts' => 0])->save();
    }

    /** Preflight: dompet komunitas butuh tBNB untuk biaya gas (auto-drip bila tersedia). */
    private function ensureGas(CommunityWallet $wallet): void
    {
        $signer = new ChainSigner();
        $eth = $signer->ethBalance($wallet->address);
        if ($eth !== null && $eth < 0.0003) {
            // Coba isi otomatis bila funder gas tersedia; kalau tetap kosong, pesan jelas.
            $this->gasDrip($wallet);
            $eth = $signer->ethBalance($wallet->address);
            abort_if($eth !== null && $eth < 0.0003, 422,
                'Dompet komunitas belum punya gas (tBNB testnet — gratis, bukan uang nyata). Isi sedikit tBNB dari faucet BNB Testnet (https://testnet.bnbchain.org/faucet-smart) ke alamat dompet (' . $wallet->address . ') lalu coba lagi.');
        }
    }

    private function communitySign(CommunityWallet $wallet, ChainSigner $signer, string $to, string|float $amount): string
    {
        $this->ensureGas($wallet);

        $priv = (new EmbeddedWallet())->decryptServer($wallet->only(['wallet_enc', 'wallet_salt', 'wallet_iv', 'wallet_tag']));
        abort_unless($priv, 500, 'Kunci dompet komunitas gagal dibuka.');
        return $signer->sendContractCall($priv, config('chain.tlkm'), self::ERC20_ABI, 'transfer', [$to, $signer->toWei((string) $amount)]);
    }

    private function resolveRecipient(string $q): array
    {
        $q = trim($q);
        if (preg_match('/^0x[a-fA-F0-9]{40}$/', $q)) {
            $u = User::where('wallet_address', strtolower($q))->first();
            return [strtolower($q), $u ? ($u->public_name ?: $u->name) : null];
        }
        $u = User::where('phone_hash', User::hashPhone($q))->orWhere('email', $q)->first();
        return $u && $u->wallet_address ? [strtolower($u->wallet_address), $u->public_name ?: $u->name] : [null, null];
    }

    private function gasDrip(CommunityWallet $wallet): void
    {
        $priv = (string) env('PLATFORM_GAS_PRIVATE_KEY', '');
        if ($priv === '') return;
        if (str_starts_with($priv, '0x')) $priv = substr($priv, 2);
        try {
            (new ChainSigner())->sendRaw($priv, $wallet->address, (new ChainSigner())->toWeiHex((string) env('GAS_DRIP_AMOUNT', '0.01')));
            $wallet->forceFill(['gas_dripped_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('Community gas drip gagal: ' . $e->getMessage());
        }
    }
}
