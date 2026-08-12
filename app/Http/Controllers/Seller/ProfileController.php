<?php
namespace App\Http\Controllers\Seller;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ProfileRequest;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Storage;
class ProfileController extends Controller
{
    public function edit(\Illuminate\Http\Request $request){ return view('seller.profile',['umkm'=>$request->user()->umkm]); }
    public function update(ProfileRequest $request, ActivityLogger $logger){ $umkm=$request->user()->umkm; $data=$request->safe()->except('foto'); if($request->hasFile('foto')){ if($umkm->foto) Storage::disk('public')->delete($umkm->foto); $data['foto']=$request->file('foto')->store('umkm','public'); } $umkm->update($data); $logger->log('Memperbarui profil UMKM',$request->user(),$request->ip()); return back()->with('success','Profil UMKM berhasil diperbarui.'); }
}
