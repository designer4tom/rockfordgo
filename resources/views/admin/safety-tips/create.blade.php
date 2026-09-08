@extends('layouts.admin')

@section('title', __('admin.new_safety_tip'))
@section('page_title', __('admin.new_safety_tip'))

@section('content')
    <form method="POST" action="{{ route('admin.safety-tips.store') }}" enctype="multipart/form-data">
        @include('admin.safety-tips._form')
    </form>
@endsection
