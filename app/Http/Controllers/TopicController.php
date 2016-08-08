<?php
namespace App\Http\Controllers;

use DB;

class TopicController extends Controller
{
    
    /*public function __construct()
    {
        $this->middleware('auth');
    }*/
    
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
        foreach($list as $topic){
            $topic->image = '/uploads/images/topics/'.$topic->id.'.png';
        }                            
        return response()->json($list);
    }
}