@if(session('success'))<div class="flash flash-success"><i class="bi bi-check-circle"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="flash flash-error"><i class="bi bi-exclamation-circle"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())<div class="flash flash-error"><i class="bi bi-exclamation-triangle"></i><div><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
