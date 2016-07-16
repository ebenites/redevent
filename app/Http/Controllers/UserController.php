<?php
namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
//use App\User;

// https://github.com/DarkaOnLine/SwaggerLume   (Solo soporta HTTP y no HTTPS)
// Cada cambio en los tags correr: php artisan swagger-lume:generate
// Example: https://github.com/zircote/swagger-php/blob/master/Examples/petstore.swagger.io/controllers/PetController.php
// Example running: http://petstore.swagger.io/
// Specification: https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md

class UserController extends Controller
{
    
    /**
     * @SWG\Post(
     *  path="/api/login",
     *  tags={"Users"},
     *  summary="Login",
     *  description="",
     *  @SWG\Parameter(
     *      in="formData",
     *      name="email",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Parameter(
     *      in="formData",
     *      name="password",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A user info"
     *  ),
     *  @SWG\Response(
     *         response="401",
     *         description="Username and/or Password Invalid",
     *  )
     * )
     */
    public function login(Request $request) 
    {
        $email = $request->input('email');
        $password = $request->input('password');
        
        $list = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE email=? AND password=?", [$email, $password]);
        
        if(count($list)==0)
            return response()->json(['message' => 'Username and/or Password Invalid'], 401);
        
        return response()->json($list[0]);
    }
    
    /**
     * @SWG\Post(
     *  path="/api/users",
     *  tags={"Users"},
     *  summary="Register",
     *  description="",
     *  @SWG\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @SWG\Schema()
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A user info"
     *  ),
     * )
     */
    public function register(Request $request) 
    {
        
        return $request->json()->all();
        
        
        $data = (object) $request->json()->all();
        
        //https://laravel.com/docs/5.1/database
        //DB::insert('insert into users (id, name) values (?, ?)', [1, 'Dayle']);
        
        return response()->json(['message' => 'Record saved!'], 200);
    }
    
    /**
     * @SWG\Get(
     *  path="/api/users/{id}",
     *  tags={"Users"},
     *  summary="Get user by id",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="id",
     *      required=true,
     *      type="integer"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A user info"
     *  ),
     *  @SWG\Response(
     *         response="404",
     *         description="Record not found",
     *  )
     * )
     */
    public function user($id) // by googleid
    {
        $list = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE id=?", [$id]);
        
        if(count($list)==0)
            return response()->json(['message' => 'Record not found'], 404);
        
        return response()->json($list[0]);
    }
    
    /**
     * @SWG\Get(
     *  path="/api/users/{id}/topics",
     *  tags={"Users"},
     *  summary="List topics by user id",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="id",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A list with topics by user id"
     *  ),
     * )
     */
    public function topics($id)
    {
        $list = DB::select("SELECT t.* FROM topics t
                            INNER JOIN preferences p ON p.topics_id=t.id
                            INNER JOIN users u ON u.id=p.users_id
                            WHERE u.id=?", [$id]);
        return response()->json($list);
    }
    
    /**
     * @SWG\Get(
     *  path="/api/users/{id}/events",
     *  tags={"Users"},
     *  summary="List events by user id",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="id",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A list with events by user id"
     *  ),
     * )
     */
    public function events($id)
    {
        $list = DB::select("SELECT t.name AS topics_name, e.* FROM topics t
                            INNER JOIN `events` e ON e.topics_id=t.id
                            INNER JOIN preferences p ON p.topics_id=t.id
                            INNER JOIN users u ON u.id=p.users_id
                            WHERE u.id=?",  [$id]);
        return response()->json($list);
    }
}