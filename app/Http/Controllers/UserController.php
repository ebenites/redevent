<?php
namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
//use App\User;

class UserController extends Controller
{
    
    /**
     * @SWG\Post(
     *  path="/api/login",
     *  tags={"Users"},
     *  summary="Login or Register",
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
     *         description="Password Invalid or Blocked account",
     *  )
     * )
     */
    public function login(Request $request) 
    {
        
        $email = $request->input('email');
        $password = $request->input('password');
        
        // Validate
        
        if(is_null($email) || trim($email) == '') // validar formato correo
            return response()->json(['message' => 'Email Required', 'status' => 400], 400);
        
        if(is_null($password) || trim($password) == '') // validar longitud minima
            return response()->json(['message' => 'Password Required', 'status' => 400], 400);
        
        // Verificar existencia
        $result = DB::select("SELECT id, email, password, googleid, status FROM users WHERE email=?", [$email]); //https://laravel.com/docs/5.1/database
        
        if(count($result)==0){
            // Registrar usuario
            DB::insert('insert into users (email, password, status) values (?, ?, 1)', [$email, Hash::make($password)]);
            
            // Get Insert ID
            $id = DB::getPdo()->lastInsertId();
            
            // Get User Detail by ID
            $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE id=?", [$id]);
            $user = $result[0];
            
            // Recuperar preferencias
            $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
            $user->topics = $topics;
            
            // Registrar Token
            $token = Hash::make( $email . ':' . $password . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
            DB::insert('insert into tokens (users_id, token, lastupdate) values (?, ?, now())', [$user->id, $token]);
            $user->token = $token;
            
            //unset($user->password);
            
            return response()->json($user);
            
        }else{
            // SI EXISTE -> Verificar Clave
            $user = $result[0];
            if (!Hash::check($password, $user->password)){
                // SI NO COINCIDE -> Lanzar error
                return response()->json(['message' => 'Password Invalid', 'status' => 401], 401);
            }else{
                // SI COINCIDE -> Verificar Estado
                if($user->status != 1){
                    // SI NO ESTA ACTIVO -> Lanzar error
                    return response()->json(['message' => 'Blocked account', 'status' => 401], 401);
                }else{
                    // SI ESTA ACTIVO -> Registrar Token
                    
                    // Get User Detail
                    $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE id=?", [$user->id]);
                    $user = $result[0];
                    
                    // Recuperar preferencias
                    $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
                    $user->topics = $topics;
                    
                    // Registrar Token
                    $token = Hash::make( $email . ':' . $password . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
                    DB::insert('insert into tokens (users_id, token, lastupdate) values (?, ?, now())', [$user->id, $token]);
                    $user->token = $token;
                    
                    //unset($user->password);
                    
                    return response()->json($user);
                }
            }
        }
        
        // var_dump($result);die();
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