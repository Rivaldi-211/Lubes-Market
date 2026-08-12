@php $key=strtolower(str_replace([' ','-'],'_', $status)); @endphp
<span class="status-badge status-{{ $key }}">{{ $status }}</span>
