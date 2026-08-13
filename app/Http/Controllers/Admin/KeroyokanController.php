<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\KelompokKeroyokan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeroyokanController extends Controller
{
    public function index(): View
    {
        $groups = KelompokKeroyokan::query()
            ->with(['kategori', 'produk.umkm'])
            ->withCount('produk')
            ->latest()
            ->paginate(15);

        return view('admin.keroyokan.index', compact('groups'));
    }

    public function create(): View
    {
        $categories = Kategori::orderBy('nama_kategori')->get();
        return view('admin.keroyokan.form', [
            'group' => new KelompokKeroyokan(),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'nama_kelompok' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ]);

        $group = KelompokKeroyokan::create($data);
        $logger->log("Admin membuat Kelompok Keroyokan: {$group->nama_kelompok}", $request->user(), $request->ip());

        return redirect()->route('admin.keroyokan.index')->with('success', 'Kelompok Keroyokan berhasil dibuat.');
    }

    public function edit(KelompokKeroyokan $keroyokan): View
    {
        $categories = Kategori::orderBy('nama_kategori')->get();
        return view('admin.keroyokan.form', [
            'group' => $keroyokan,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, KelompokKeroyokan $keroyokan, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'nama_kelompok' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ]);

        $keroyokan->update($data);
        $logger->log("Admin memperbarui Kelompok Keroyokan: {$keroyokan->nama_kelompok}", $request->user(), $request->ip());

        return redirect()->route('admin.keroyokan.index')->with('success', 'Kelompok Keroyokan berhasil diperbarui.');
    }

    public function destroy(Request $request, KelompokKeroyokan $keroyokan, ActivityLogger $logger): RedirectResponse
    {
        $name = $keroyokan->nama_kelompok;
        $keroyokan->produk()->update(['kelompok_keroyokan_id' => null]);
        $keroyokan->delete();

        $logger->log("Admin menghapus Kelompok Keroyokan: {$name}", $request->user(), $request->ip());

        return redirect()->route('admin.keroyokan.index')->with('success', 'Kelompok Keroyokan berhasil dihapus.');
    }
}
