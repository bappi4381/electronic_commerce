@extends('admin.layouts')
@section('title', 'Edit article')
@section('ui-v2', '1')

@section('content')
<form method="POST" action="{{ route('articles.update', $article->id) }}" enctype="multipart/form-data" id="articleForm">
    @csrf @method('PUT')
    <x-admin.page-header title="Edit article" :description="$article->title"
                         :breadcrumbs="['Tech blog' => route('articles.index'), \Illuminate\Support\Str::limit($article->title, 40) => route('articles.show', $article), 'Edit' => null]">
        <x-admin.button variant="ghost" :href="route('frontend.articles.show', $article->slug)" target="_blank" rel="noopener" icon="bi-box-arrow-up-right">View on site</x-admin.button>
        <x-admin.button variant="secondary" :href="route('articles.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
    </x-admin.page-header>

    @include('admin.article._form', ['article' => $article])
</form>
@endsection
