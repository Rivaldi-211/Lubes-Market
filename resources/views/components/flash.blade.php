@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded',function(){
    Swal.fire({title:'Berhasil',text:{!! json_encode(session('success')) !!},icon:'success',confirmButtonText:'OK',customClass:{popup:'swal-dark-gold',confirmButton:'swal-gold-btn'},background:'#1a1f1a',color:'#f5f1e7'});
});
</script>
@endif
@if(session('error'))
<script>
document.addEventListener('DOMContentLoaded',function(){
    Swal.fire({title:'Terjadi Kesalahan',text:{!! json_encode(session('error')) !!},icon:'error',confirmButtonText:'OK',customClass:{popup:'swal-dark-gold',confirmButton:'swal-gold-btn'},background:'#1a1f1a',color:'#f5f1e7'});
});
</script>
@endif
@if(session('warning'))
<script>
document.addEventListener('DOMContentLoaded',function(){
    Swal.fire({title:'Perhatian',text:{!! json_encode(session('warning')) !!},icon:'warning',confirmButtonText:'OK',customClass:{popup:'swal-dark-gold',confirmButton:'swal-gold-btn'},background:'#1a1f1a',color:'#f5f1e7'});
});
</script>
@endif
@if(session('info'))
<script>
document.addEventListener('DOMContentLoaded',function(){
    Swal.fire({title:'Informasi',text:{!! json_encode(session('info')) !!},icon:'info',confirmButtonText:'OK',customClass:{popup:'swal-dark-gold',confirmButton:'swal-gold-btn'},background:'#1a1f1a',color:'#f5f1e7'});
});
</script>
@endif
@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded',function(){
    Swal.fire({title:'Periksa kembali data Anda',icon:'error',html:'<ul style="text-align:left;margin:0;padding-left:18px;color:#e8dcc8">'+{!! json_encode(collect($errors->all())->map(fn($e)=>'<li>'.$e.'</li>')->implode('')) !!}+'</ul>',confirmButtonText:'OK',customClass:{popup:'swal-dark-gold',confirmButton:'swal-gold-btn'},background:'#1a1f1a',color:'#f5f1e7'});
});
</script>
@endif
