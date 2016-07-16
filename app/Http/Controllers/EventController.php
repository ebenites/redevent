<?php
namespace App\Http\Controllers;

use DB;

class EventController extends Controller
{
    /**
     * @SWG\Get(
     *  path="/api/events",
     *  tags={"Events"},
     *  summary="List events",
     *  description="",
     *  @SWG\Response(
     *      response=200,
     *      description="A list with events"
     *  ),
     * )
     */
    public function events()
    {
        $list = DB::select("SELECT * FROM events");
        return response()->json($list);
    }

}