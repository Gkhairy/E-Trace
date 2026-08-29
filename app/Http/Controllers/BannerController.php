<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isSupervisor(), 403, 'Hanya admin/pengawas yang bisa mengelola iklan.');
    }

    /** Halaman kelola iklan (banner carousel). */
    public function index()
    {
        $this->ensureAdmin();
        $banners = Banner::orderBy('sort')->orderByDesc('id')->get();
        return view('admin.banners', compact('banners'));
    }

    /** Tambah banner iklan (upload gambar atau URL). */
    public function store(Request $req)
    {
        $this->ensureAdmin();

        $data = $req->validate([
            'title'      => 'nullable|string|max:120',
            'link'       => 'nullable|url|max:500',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'image_url'  => 'nullable|url|max:500',
            'sort'       => 'nullable|integer|min:0',
        ]);

        // Gambar: file upload ATAU URL.
        $image = null;
        if ($req->hasFile('image')) {
            $image = Str::uuid() . '.' . $req->file('image')->getClientOriginalExtension();
            $req->file('image')->move(public_path('banner_images'), $image);
        } elseif (!empty($data['image_url'])) {
            $image = $data['image_url'];
        }
        if (!$image) {
            return back()->with('error', 'Sertakan gambar (upload atau URL).');
        }

        Banner::create([
            'title'     => $data['title'] ?? null,
            'image'     => $image,
            'link'      => $data['link'] ?? null,
            'sort'      => $data['sort'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Iklan ditambahkan.');
    }

    /** Aktif/nonaktifkan banner. */
    public function toggle(Request $req)
    {
        $this->ensureAdmin();
        $banner = Banner::findOrFail($req->id);
        $banner->is_active = !$banner->is_active;
        $banner->save();
        return back()->with('success', 'Status iklan diperbarui.');
    }

    /** Hapus banner. */
    public function destroy(Request $req)
    {
        $this->ensureAdmin();
        $banner = Banner::findOrFail($req->id);
        if ($banner->image && !str_starts_with($banner->image, 'http')) {
            @unlink(public_path('banner_images/' . $banner->image));
        }
        $banner->delete();
        return back()->with('success', 'Iklan dihapus.');
    }
}
