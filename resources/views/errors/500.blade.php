@extends('errors.layout')

@section('title', 'Estamos ajustando algunos detalles')

@section('meta_description', 'Hubo un inconveniente temporal. Puedes reintentar en unos momentos.')

@section('content')
    @include('errors.partials.http-card', [
        'code' => '500',
        'badge' => 'Servicio',
        'title' => 'Nuestros servidores necesitan un respiro',
        'message' => 'Ocurrió un problema inesperado en el sistema. Ya estamos trabajando para solucionarlo y que puedas seguir compartiendo tu impacto positivo.',
        'severity' => 'critical',
    ])
@endsection
