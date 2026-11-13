@extends('admin.layouts')
@section('title', 'Write article')
@section('ui-v2', '1')

@section('content')
<form method="POST" action="{{ route('articles.store') }}" enctype="multipart/form-data" id="articleForm">
    @csrf
    <x-admin.page-header title="Write article" description="Draft a new post for the tech blog."
                         :breadcrumbs="['Tech blog' => route('articles.index'), 'Write article' => null]">
        <x-admin.button variant="secondary" :href="route('articles.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Save article</x-admin.button>
    </x-admin.page-header>

    @include('admin.article._form')
</form>
@endsection
