@extends('errors.layout')

@section('title', 'Necesitas iniciar sesión')

@section('content')
    @include('errors.partials.http-card', [
        'code' => '401',
        'badge' => 'Acceso',
        'title' => 'Primero pasa por la mesa de bienvenida',
        'message' => 'Para continuar, inicia sesión y retomamos tu experiencia sostenible.',
        'severity' => 'soft',
    ])
@endsection

