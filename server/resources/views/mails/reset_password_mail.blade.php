@extends('layouts.mail')

@section('title', __('password reset'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">@lang('password reset')</div>

                <div class="card-body">
                    <a href='{{$url}}'>@lang('click this link to go to password reset.')</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
