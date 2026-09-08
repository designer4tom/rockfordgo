@extends('layouts.admin')

@section('title', __('admin.edit_page'))
@section('page_title', __('admin.edit_page'))

@section('content')
    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}">
        @method('PUT')
        @include('admin.pages._form')
    </form>
@endsection
