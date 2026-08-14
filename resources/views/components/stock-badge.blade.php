@props(['product'])
@php($status=$product->effectiveStockStatus())
<span class="stock-badge stock-{{ strtolower(str_replace([' ','-'],'_',$status)) }}">@if($status === 'Pre-Order')Pre-Order ({{ $product->estimasi_po_hari ? $product->estimasi_po_hari.' Hari' : 'PO' }})@else{{ $status }}@if($status !== 'Habis') · {{ $product->stok_jumlah }}@endif @endif</span>
