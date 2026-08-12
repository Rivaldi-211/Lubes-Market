@props(['product'])
@php($status=$product->effectiveStockStatus())
<span class="stock-badge stock-{{ strtolower(str_replace([' ','-'],'_',$status)) }}">{{ $status }}@if($status !== 'Habis') · {{ $product->stok_jumlah }}@endif</span>
