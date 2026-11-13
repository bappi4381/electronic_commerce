@extends('errors.shell')

@section('title', 'Access denied')
@section('code', '403')
@section('icon', 'bi-shield-lock')
@section('heading', 'You don’t have access to this page')
@section('message')
    {{ request()->is('admin', 'admin/*') ? 'Your role doesn’t include the permission this page needs. Ask a Super Admin if you think that’s a mistake.' : 'You’re not allowed to view this page.' }}
@endsection
