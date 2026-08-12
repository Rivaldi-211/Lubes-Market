<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\LogAktivitas; use Illuminate\Http\Request;
class ActivityLogController extends Controller
{
    public function index(Request $request){ $q=LogAktivitas::with('user'); if($request->filled('q'))$q->where('aktivitas','like','%'.$request->input('q').'%'); $logs=$q->latest()->paginate(30)->withQueryString(); return view('admin.logs.index',compact('logs')); }
}
