<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\RekeningBankRequest;
use App\Models\RekeningBank;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekeningBankController extends Controller
{
    private function own(Request $request, RekeningBank $rekeningBank): void
    {
        $umkm = $request->user()->umkm;
        abort_unless($umkm && (int)$rekeningBank->umkm_id === (int)$umkm->id, 403);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $umkm = $request->user()->umkm;
        if (!$umkm) {
            return redirect()->route('seller.dashboard')->with('error', 'Profil UMKM Anda belum dikonfigurasi.');
        }

        $accounts = RekeningBank::forUmkm($umkm->id)
            ->orderBy('urutan')
            ->latest('id')
            ->paginate(15);

        return view('seller.rekening-bank.index', compact('accounts'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $umkm = $request->user()->umkm;
        if (!$umkm) {
            return redirect()->route('seller.dashboard')->with('error', 'Profil UMKM Anda belum dikonfigurasi.');
        }

        return view('seller.rekening-bank.form', [
            'account' => new RekeningBank(),
            'isEdit' => false,
        ]);
    }

    public function store(RekeningBankRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $umkm = $request->user()->umkm;
        if (!$umkm) {
            return redirect()->route('seller.dashboard')->with('error', 'Profil UMKM Anda belum dikonfigurasi.');
        }

        $data = $request->validated();
        $data['umkm_id'] = $umkm->id;
        $data['aktif'] = $request->boolean('aktif', true);
        $data['urutan'] = (int) ($data['urutan'] ?? 0);

        $bank = RekeningBank::create($data);
        $logger->log("Menambahkan rekening bank {$bank->nama_bank} ({$bank->nomor_rekening})", $request->user(), $request->ip());

        return redirect()->route('seller.rekening-bank.index')->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function edit(Request $request, RekeningBank $rekeningBank): View
    {
        $this->own($request, $rekeningBank);

        return view('seller.rekening-bank.form', [
            'account' => $rekeningBank,
            'isEdit' => true,
        ]);
    }

    public function update(RekeningBankRequest $request, RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        $this->own($request, $rekeningBank);

        $data = $request->validated();
        $data['aktif'] = $request->boolean('aktif');
        $data['urutan'] = (int) ($data['urutan'] ?? 0);

        $rekeningBank->update($data);
        $logger->log("Mengubah rekening bank #{$rekeningBank->id} - {$rekeningBank->nama_bank}", $request->user(), $request->ip());

        return redirect()->route('seller.rekening-bank.index')->with('success', 'Rekening bank berhasil diperbarui.');
    }

    public function status(Request $request, RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        $this->own($request, $rekeningBank);
        $rekeningBank->update(['aktif' => !$rekeningBank->aktif]);

        $statusText = $rekeningBank->aktif ? 'diaktifkan' : 'dinonaktifkan';
        $logger->log("Ubah status rekening bank #{$rekeningBank->id} menjadi {$statusText}", $request->user(), $request->ip());

        return back()->with('success', "Rekening bank berhasil {$statusText}.");
    }

    public function destroy(Request $request, RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        $this->own($request, $rekeningBank);
        $name = $rekeningBank->nama_bank;
        $rekeningBank->delete();

        $logger->log("Menghapus rekening bank {$name}", $request->user(), $request->ip());

        return redirect()->route('seller.rekening-bank.index')->with('success', 'Rekening bank berhasil dihapus.');
    }
}
