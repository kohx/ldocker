@extends('layouts.mail')

@section('title', '登録認証')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">登録認証</div>

                <div class="card-body">
                    <a href='{{$url}}'>こちらのリンク</a>をクリックして、メールを認証してください。
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
