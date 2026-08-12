<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Http\Requests\Admin\UserStatusRequest; use App\Models\User; use App\Services\ActivityLogger; use Illuminate\Http\Request; use Illuminate\Validation\ValidationException;
class UserController extends Controller
{
    public function index(Request $request){ $q=User::with('umkm'); if($request->filled('role'))$q->where('role',$request->input('role')); if($request->filled('q'))$q->where(fn($x)=>$x->where('nama_lengkap','like','%'.$request->input('q').'%')->orWhere('username','like','%'.$request->input('q').'%')); $users=$q->latest()->paginate(20)->withQueryString(); return view('admin.users.index',compact('users')); }
    public function status(UserStatusRequest $request, User $user, ActivityLogger $logger){ $status=$request->validated('status'); if($user->id===$request->user()->id && $status==='nonaktif') throw ValidationException::withMessages(['status'=>'Akun admin yang sedang digunakan tidak dapat dinonaktifkan.']); $user->update(['status'=>$status]); $logger->log("Mengubah status akun {$user->username} menjadi {$status}",$request->user(),$request->ip()); return back()->with('success','Status pengguna diperbarui.'); }
}
