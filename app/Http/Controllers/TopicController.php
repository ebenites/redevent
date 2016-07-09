<?php
namespace App\Http\Controllers;

//use App\User;
use DB;

class TopicController extends Controller
{
    /**
     * Retrieve the topics list
     *
     * @param  int  $id
     * @return Response
     */
    public function topics()
    {
        $list = DB::select("SELECT * FROM topics");
        return response()->json($list);
    }
}