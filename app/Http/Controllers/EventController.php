<?php
namespace App\Http\Controllers;

use DB;

class EventController extends Controller
{
    public function events()
    {
        $list = DB::select("SELECT * FROM events");
        return response()->json($list);
    }
}