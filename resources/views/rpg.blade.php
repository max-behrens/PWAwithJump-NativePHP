@extends('layouts.app')

@section('container_class', 'p-0')

@section('content')
  <div id="rpg-app"></div>
@endsection

@push('styles')
  @vite(['resources/js/rpg.js'])
@endpush