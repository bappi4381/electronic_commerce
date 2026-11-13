@extends('errors.shell')

@section('title', 'Page expired')
@section('code', '419')
@section('icon', 'bi-hourglass-split')
@section('heading', 'This page has expired')
@section('message', 'Your session timed out before the form was sent. Go back, refresh the page and try again. Nothing was saved.')
@section('secondary')
    <button type="button" onclick="history.back()"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus-ring dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Go back and retry
    </button>
@endsection
