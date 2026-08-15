@extends('layouts.auth')

@section('title', 'Onboarding & Verifikasi Penjual')

@section('content')
<div class="auth-box" style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <span style="font-size: 11px; font-weight: 800; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 999px; text-transform: uppercase;">
            Langkah 2 dari 2 — Formulir Verifikasi Usaha
        </span>
        <h2 style="font-size: 1.6rem; font-weight: 800; margin: 10px 0 6px 0; color: #0f172a;">Lengkapi Informasi Usaha Anda</h2>
        <p style="color: #64748b; font-size: 0.92rem; margin: 0;">
            Agar dapat berjualan di LUDES-MARKET, admin perlu memastikan legalitas dan kesiapan produk usaha Anda.
        </p>
    </div>

    <form method="post" action="{{ route('seller.onboarding.store') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 18px;">
        @csrf

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
            <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                1. Deskripsi Singkat Produk <span style="color:#b91c1c">*</span>
            </label>
            <textarea name="deskripsi_produk" rows="3" class="form-control" required placeholder="Contoh: Menjual keripik pisang aneka rasa khas Moncongloe dibuat dari bahan lokal..." style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">{{ old('deskripsi_produk') }}</textarea>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
            <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                2. Estimasi Kapasitas Stok per Minggu (unit/porsi/pck) <span style="color:#b91c1c">*</span>
            </label>
            <input type="number" min="1" name="kapasitas_mingguan" value="{{ old('kapasitas_mingguan', 50) }}" class="form-control" required style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
            <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 8px;">
                3. Apakah Anda Memiliki Izin Usaha (NIB / P-IRT / SIUP / Dll)? <span style="color:#b91c1c">*</span>
            </label>
            <div style="display: flex; gap: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="punya_izin" value="ya" @checked(old('punya_izin') === 'ya') onchange="document.getElementById('izinField').style.display = 'block'"> Ya, Memiliki
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="punya_izin" value="tidak" @checked(old('punya_izin', 'tidak') === 'tidak') onchange="document.getElementById('izinField').style.display = 'none'"> Belum Ada
                </label>
            </div>
            <div id="izinField" style="display: {{ old('punya_izin') === 'ya' ? 'block' : 'none' }}; margin-top: 12px;">
                <input type="text" name="nomor_izin" value="{{ old('nomor_izin') }}" placeholder="Nomor NIB / P-IRT / Izin Usaha" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
            <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                4. Bagaimana Metode Kemasan / Packing Produk Anda? <span style="color:#b91c1c">*</span>
            </label>
            <textarea name="cara_kemas" rows="2" class="form-control" required placeholder="Contoh: Menggunakan ziplock pouch press kedap udara dan bubble wrap..." style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">{{ old('cara_kemas') }}</textarea>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
            <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 8px;">
                5. Bersedia Memenuhi Pesanan dalam Waktu Maksimal 1x24 Jam? <span style="color:#b91c1c">*</span>
            </label>
            <div style="display: flex; gap: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="sanggup_24jam" value="ya" @checked(old('sanggup_24jam', 'ya') === 'ya')> Ya, Sangggup
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="radio" name="sanggup_24jam" value="tidak" @checked(old('sanggup_24jam') === 'tidak')> Butuh Waktu Lebih
                </label>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                    6. Upload Foto KTP Pemilik <span style="color:#b91c1c">*</span>
                </label>
                <input type="file" name="foto_ktp" accept="image/jpeg,image/png,image/webp" required style="width: 100%;">
                <small style="color: #64748b; font-size: 11px;">Format JPG, PNG (maks 5MB)</small>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <label style="display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px;">
                    7. Upload Foto Produk Unggulan <span style="color:#b91c1c">*</span>
                </label>
                <input type="file" name="foto_produk" accept="image/jpeg,image/png,image/webp" required style="width: 100%;">
                <small style="color: #64748b; font-size: 11px;">Foto fisik produk / sampel kemasan</small>
            </div>
        </div>

        <button type="submit" class="button" style="margin-top: 10px; width: 100%; justify-content: center; font-size: 1rem; padding: 12px;">
            <i class="bi bi-send-check"></i> Kirim Formulir Verifikasi Usaha
        </button>
    </form>
</div>
@endsection
