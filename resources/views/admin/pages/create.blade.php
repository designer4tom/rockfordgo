@extends('layouts.admin')

@section('title', __('admin.new_page'))
@section('page_title', __('admin.new_page'))

@section('content')
    <form method="POST" action="{{ route('admin.pages.store') }}">
        @include('admin.pages._form')
    </form>
@endsection
