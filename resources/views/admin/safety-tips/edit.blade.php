@extends('layouts.admin')

@section('title', __('admin.edit_safety_tip'))
@section('page_title', __('admin.edit_safety_tip'))

@section('content')
    <form method="POST" action="{{ route('admin.safety-tips.update', $tip->id) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.safety-tips._form')
    </form>
@endsection
