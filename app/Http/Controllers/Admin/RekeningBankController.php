<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RekeningBankRequest;
use App\Models\RekeningBank;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RekeningBankController extends Controller
{
    public function index(): View
    {
        $accounts = RekeningBank::query()
            ->orderBy('urutan', 'asc')
            ->orderBy('nama_bank', 'asc')
            ->paginate(15);

        return view('admin.rekening-bank.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.rekening-bank.form', ['account' => new RekeningBank()]);
    }

    public function store(RekeningBankRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $data['aktif'] = $request->has('aktif') ? (bool)$request->aktif : true;
        $data['urutan'] = $data['urutan'] ?? 0;

        $account = RekeningBank::create($data);
        $logger->log("Menambahkan rekening bank platform {$account->nama_bank} ({$account->nomor_rekening})", $request->user(), $request->ip());

        return redirect()->route('admin.rekening-bank.index')->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function edit(RekeningBank $rekeningBank): View
    {
        return view('admin.rekening-bank.form', ['account' => $rekeningBank]);
    }

    public function update(RekeningBankRequest $request, RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validated();
        $data['aktif'] = $request->has('aktif') ? (bool)$request->aktif : false;
        $data['urutan'] = $data['urutan'] ?? 0;

        $rekeningBank->update($data);
        $logger->log("Memperbarui rekening bank platform {$rekeningBank->nama_bank} ({$rekeningBank->nomor_rekening})", $request->user(), $request->ip());

        return redirect()->route('admin.rekening-bank.index')->with('success', 'Rekening bank berhasil diperbarui.');
    }

    public function status(RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        $rekeningBank->update(['aktif' => !$rekeningBank->aktif]);
        $statusStr = $rekeningBank->aktif ? 'diaktifkan' : 'dinonaktifkan';
        $logger->log("Status rekening bank {$rekeningBank->nama_bank} {$statusStr}", request()->user(), request()->ip());

        return back()->with('success', "Rekening bank berhasil {$statusStr}.");
    }

    public function destroy(RekeningBank $rekeningBank, ActivityLogger $logger): RedirectResponse
    {
        if ($rekeningBank->pesanan()->exists()) {
            return back()->with('error', 'Rekening bank ini memiliki riwayat transaksi pesanan dan tidak dapat dihapus. Silakan nonaktifkan statusnya agar tidak tampil di checkout.');
        }

        $nama = $rekeningBank->nama_bank;
        $no = $rekeningBank->nomor_rekening;
        $rekeningBank->delete();
        $logger->log("Menghapus rekening bank platform {$nama} ({$no})", request()->user(), request()->ip());

        return redirect()->route('admin.rekening-bank.index')->with('success', 'Rekening bank berhasil dihapus.');
    }
}
