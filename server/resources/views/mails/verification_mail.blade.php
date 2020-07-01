@extends('layouts.mail')

@section('title', __('registration certification'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">@lang('registration certification')</div>

                <div class="card-body">
                    <a href='{{$url}}'>@lang('please click this link to verify your email.')</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
