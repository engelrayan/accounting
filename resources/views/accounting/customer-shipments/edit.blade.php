@extends('accounting._layout')

@section('title', 'تعديل شحنات العملاء')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">تعديل شحنات يوم {{ $batch->shipment_date->format('Y-m-d') }}</h1>
    <a href="{{ route('accounting.customer-shipments.show', $batch) }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@php
    $formAction = route('accounting.customer-shipments.update', $batch);
    $formMethod = 'PUT';
    $submitLabel = 'حفظ التعديلات';
@endphp

@include('accounting.customer-shipments._form', compact('formAction', 'formMethod', 'submitLabel', 'batch', 'customers', 'governorates'))
@endsection
