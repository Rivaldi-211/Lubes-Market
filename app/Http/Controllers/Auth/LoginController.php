<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
class LoginController extends Controller
{
    public function create(): View { return view('auth.login'); }
    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $username=(string)$request->input('username'); $password=(string)$request->input('password');
        $user=User::where('username',$username)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withErrors(['username'=>'Username atau password tidak sesuai.'])->onlyInput('username');
        }
        if (!$user->isActive()) {
            return back()->withErrors(['username'=>'Akun Anda sedang dinonaktifkan.'])->onlyInput('username');
        }
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $logger->log('Login ke sistem', $user, $request->ip());
        return redirect()->intended($user->dashboardPath());
    }
    public function destroy(\Illuminate\Http\Request $request, ActivityLogger $logger): RedirectResponse
    {
        if ($request->user()) $logger->log('Logout dari sistem', $request->user(), $request->ip());
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
