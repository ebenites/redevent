<?php
namespace App\Http\Controllers;

use DB;

class TopicController extends Controller
{
    /**
     * @SWG\Get(
     *  path="/api/topics",
     *  tags={"Topics"},
     *  summary="List topics",
     *  description="",
     *  @SWG\Response(
     *      response=200,
     *      description="A list with topics"
     *  ),
     * )
     */
    public function topics()
    {
        $list = DB::select("SELECT * FROM topics");
        return response()->json($list);
    }
}