<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Auth;

class TestController extends Controller
{
    public function index(){

        $photo = new Photo();
        $photo->filename = "aaa";
        $res = Auth::user()->photos()->save($photo);
        dd($res);
    }
}
