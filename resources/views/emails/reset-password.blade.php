<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — LUDES-MARKET</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; line-height: 1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f8; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" max-width="580" style="max-width: 580px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #123825; padding: 28px 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: 0.5px;">
                                <span style="color: #eab308;">LUDES</span>-MARKET
                            </h1>
                            <p style="margin: 4px 0 0 0; color: #9fb9aa; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                                Moncongloe Lappara
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 28px;">
                            <h2 style="margin: 0 0 14px; font-size: 19px; color: #0f172a; font-weight: 700;">
                                Halo, {{ $user->nama_lengkap }}!
                            </h2>
                            <p style="margin: 0 0 18px; color: #475569; font-size: 14px;">
                                Kami menerima permintaan untuk mereset password akun Anda di platform <strong>LUDES-MARKET</strong> dengan username <strong>{{ '@' . $user->username }}</strong>.
                            </p>
                            <p style="margin: 0 0 26px; color: #475569; font-size: 14px;">
                                Silakan klik tombol di bawah ini untuk membuat password baru Anda:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto 28px auto;">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background-color: #b48325;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 8px; background-color: #b48325;">
                                            Atur Ulang Password Saya &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #f8fafc; border-left: 4px solid #b48325; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 12px; color: #64748b;">
                                    ⏳ <strong>Penting:</strong> Tautan ini hanya berlaku selama <strong>60 menit</strong> sejak email ini dikirimkan.
                                </p>
                            </div>

                            <p style="margin: 0 0 12px; font-size: 12px; color: #64748b;">
                                Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut ke peramban (browser) Anda:
                            </p>
                            <p style="margin: 0 0 24px; font-size: 11px; word-break: break-all; color: #0284c7; background: #f1f5f9; padding: 8px 12px; border-radius: 6px;">
                                <a href="{{ $resetUrl }}" style="color: #0284c7; text-decoration: underline;">{{ $resetUrl }}</a>
                            </p>

                            <p style="margin: 0; font-size: 13px; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 18px;">
                                Jika Anda tidak pernah merasa meminta reset password, Anda dapat mengabaikan email ini dengan aman. Password akun Anda tidak akan berubah tanpa tautan di atas.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                &copy; {{ date('Y') }} LUDES-MARKET — Platform Pemasaran Produk UMKM Moncongloe Lappara.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
