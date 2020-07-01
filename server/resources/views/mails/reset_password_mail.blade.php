@extends('layouts.mail')

@section('title', 'パスワードリセット')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">パスワードリセット</div>

                <div class="card-body">
                    <a href='{{$url}}'>こちらのリンク</a>をクリックして、パスワードリセットしてください。
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
