<?php
namespace App\Http\Controllers;

use DB;

class UserController extends Controller
{
    public function user($email) // by googleid
    {
        $list = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE email=?", [$email]);
        return response()->json($list);
    }
}