@extends('errors.layout')

@section('title', 'Esta receta no aparece en el menú')

@section('meta_description', 'La página que buscabas no está disponible. Vuelve al inicio de EcoShare.')

@section('content')
    @include('errors.partials.http-card', [
        'code' => '404',
        'badge' => 'EcoShare',
        'title' => 'Esta receta no aparece en el menú',
        'message' => 'El enlace puede haber cambiado o este plato ya no está en carta. El inicio tiene buenas opciones para seguir explorando.',
        'severity' => 'soft',
    ])
@endsection
