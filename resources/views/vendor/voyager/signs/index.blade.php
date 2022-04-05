@extends('voyager::master')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop
@section('page_title', __("Sign file"))
@section('page_header')
    <h1 class="page-title">
        <i class="voyager-lock"></i>
        {{ __("Sign New file") }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop
@section('content')
<div class="page-content edit-add container-fluid">
    <div class="row">
        <div class="col-md-12">

            <div class="panel panel-bordered">
                <!-- form start -->
                <form role="form"
                        class="form-edit-add"
                        action=""
                        method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <div class="panel-body">
                            <legend class="text-{{ 'center' }}" style="background-color: {{  '#f0f0f0' }};padding: 5px;">{{ "file here" }}</legend>
                        </div><!-- panel-body -->

                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                        </div>
                    </form>
@endsection


