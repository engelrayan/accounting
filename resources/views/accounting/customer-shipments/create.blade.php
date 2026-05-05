@extends('accounting._layout')

@section('title', 'شحنات العملاء')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">إدخال شحنات العملاء</h1>
    <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@php
    $formAction = route('accounting.customer-shipments.store');
    $formMethod = 'POST';
    $submitLabel = 'حفظ وفتح المراجعة';
@endphp

@include('accounting.customer-shipments._form', compact('formAction', 'formMethod', 'submitLabel', 'customers', 'governorates'))
@endsection
