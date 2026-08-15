<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStatusRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with(['umkm'])->withCount('pesanan');
        if ($request->filled('role')) {
            $q->where('role', $request->input('role'));
        }
        if ($request->filled('q')) {
            $queryStr = $request->input('q');
            $q->where(function ($x) use ($queryStr) {
                $x->where('nama_lengkap', 'like', '%' . $queryStr . '%')
                  ->orWhere('username', 'like', '%' . $queryStr . '%')
                  ->orWhere('email', 'like', '%' . $queryStr . '%')
                  ->orWhere('no_hp', 'like', '%' . $queryStr . '%');
            });
        }
        $users = $q->latest()->paginate(20)->withQueryString();
        $totalUsers = User::count();
        $totalPembeli = User::where('role', 'pembeli')->count();
        $totalPenjual = User::where('role', 'penjual')->count();
        $totalAdmin = User::where('role', 'admin')->count();

        return view('admin.users.index', compact('users', 'totalUsers', 'totalPembeli', 'totalPenjual', 'totalAdmin'));
    }

    public function status(UserStatusRequest $request, User $user, ActivityLogger $logger)
    {
        $status = $request->validated('status');
        if ($user->id === $request->user()->id && $status === 'nonaktif') {
            throw ValidationException::withMessages(['status' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan.']);
        }
        $user->update(['status' => $status]);
        $logger->log("Mengubah status akun {$user->username} menjadi {$status}", $request->user(), $request->ip());
        return back()->with('success', 'Status pengguna diperbarui.');
    }
}
