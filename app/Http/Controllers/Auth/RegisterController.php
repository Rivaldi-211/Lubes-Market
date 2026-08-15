<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Umkm;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class RegisterController extends Controller
{
    public function create(): View { return view('auth.register'); }
    public function store(RegisterRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data=$request->validated();
        $user=DB::transaction(function() use ($data) {
            $user=User::create([
                'nama_lengkap'=>$data['nama_lengkap'],'username'=>$data['username'],'email'=>$data['email']??null,
                'no_hp'=>$data['no_hp']??null,'password'=>$data['password'],'role'=>$data['role'],'status'=>'aktif',
            ]);
            if ($user->isSeller()) {
                Umkm::create([
                    'user_id'=>$user->id,
                    'nama_umkm'=>$data['nama_umkm'],
                    'pemilik'=>$user->nama_lengkap,
                    'alamat'=>$data['alamat']??'Desa Moncongloe Lappara',
                    'no_hp'=>$user->no_hp,
                    'status'=>'nonaktif',
                    'status_verifikasi'=>'menunggu'
                ]);
            }
            return $user;
        });
        Auth::login($user); $request->session()->regenerate();
        $logger->log('Mendaftarkan akun '.$user->role, $user, $request->ip());

        if ($user->isSeller()) {
            return redirect()->route('seller.onboarding')->with('info', 'Akun berhasil dibuat. Lengkapi informasi usaha Anda untuk diverifikasi admin.');
        }

        return redirect($user->dashboardPath())->with('success','Akun berhasil dibuat. Selamat datang!');
    }
}
