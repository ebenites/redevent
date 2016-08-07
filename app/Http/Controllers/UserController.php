<?php
namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Validator;
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
        try{
            
            // dd($request);    // dd() = var_dump + exit; 
            
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
                
                DB::beginTransaction();
                
                // Registrar usuario
                DB::insert('insert into users (email, password, status) values (?, ?, 1)', [$email, Hash::make($password)]);
                
                // Get Insert ID
                $id = DB::getPdo()->lastInsertId();
                
                // Get User Detail by ID
                $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status, IF(ISNULL(file), NULL, 1) as photo FROM users WHERE id=?", [$id]);
                $user = $result[0];
                
                if($user->photo == 1){
                    $user->photo = '/people/'.$user->id.'/';
                }
                
                // Recuperar preferencias
                $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
                $user->topics = $topics;
                
                // Registrar Token
                $token = Hash::make( $email . ':' . $password . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
                DB::insert('INSERT INTO tokens (users_id, token, lastupdate) VALUES (?, ?, now())', [$user->id, $token]);
                $user->token = $token;
                
                unset($user->password);
                unset($user->status);
                
                DB::commit();
                
                return response()->json($user);
                
            }else{
                
                DB::beginTransaction();
                
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
                        $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status, IF(ISNULL(file), NULL, 1) as photo FROM users WHERE id=?", [$user->id]);
                        $user = $result[0];
                        
                        if($user->photo == 1){
                            $user->photo = '/people/'.$user->id.'/';
                        }
                        
                        // Recuperar preferencias
                        $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
                        $user->topics = $topics;
                        
                        // Registrar Token
                        $token = Hash::make( $email . ':' . $password . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
                        DB::insert('INSERT INTO tokens (users_id, token, lastupdate) VALUES (?, ?, now())', [$user->id, $token]);
                        $user->token = $token;
                        
                        unset($user->password);
                        unset($user->status);
                        
                        DB::commit();
                        
                        return response()->json($user);
                    }
                }
            }
            
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => 500], 500);
        }
    }
    
        /**
     * @SWG\Post(
     *  path="/api/glogin",
     *  tags={"Users"},
     *  summary="Login or Register with Google",
     *  description="",
     *  @SWG\Parameter(
     *      in="formData",
     *      name="email",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Parameter(
     *      in="formData",
     *      name="googleid",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Parameter(
     *      in="formData",
     *      name="fullname",
     *      required=false,
     *      type="string"
     *  ),
     *  @SWG\Parameter(
     *      in="formData",
     *      name="photo",
     *      required=false,
     *      type="file"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="A user info"
     *  ),
     *  @SWG\Response(
     *         response="401",
     *         description="GoogleID Invalid or Blocked account",
     *  )
     * )
     */
    public function login_with_google(Request $request) 
    {
        try{
            
            $email = $request->input('email');
            $googleid = $request->input('googleid');
            $fullname = $request->input('fullname');
            
            // dd($request->file('photo'));
            
            // Validator: https://laravel.com/docs/5.2/validation
            
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'googleid' => 'required',
            ]);
            
            if ($validator->fails()) {
                // dd($validator->getMessageBag()->all());
                return response()->json(['message' => $validator->getMessageBag()->first(), 'status' => 400], 400);
            }
            
            // Validar googleid con Google API (pendiente) ...
            
            // Verificar existencia
            $result = DB::select("SELECT id, email, password, googleid, status FROM users WHERE email=?", [$email]); 
            
            if(count($result)==0){
                
                DB::beginTransaction();
                
                // Registrar usuario
                DB::insert('insert into users (email, googleid, fullname, status) values (?, ?, ?, 1)', [$email, $googleid, $fullname]);
                
                // Get Insert ID
                $id = DB::getPdo()->lastInsertId();
                
                if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                    $photo = $request->file('photo');
                    DB::update('UPDATE users SET file=?, filetype=?, filesize=? where id=?', [file_get_contents($photo->getPathname()), $photo->getMimeType(), $photo->getSize(), $user->id]);
                }
                
                // Get User Detail by ID
                $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status, IF(ISNULL(file), NULL, 1) as photo FROM users WHERE id=?", [$id]);
                $user = $result[0];
                
                if($user->photo == 1){
                    $user->photo = '/people/'.$user->id.'/';
                }
                
                // Recuperar preferencias
                $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
                $user->topics = $topics;
                
                // Registrar Token
                $token = Hash::make( $email . ':' . $password . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
                DB::insert('INSERT INTO tokens (users_id, token, lastupdate) VALUES (?, ?, now())', [$user->id, $token]);
                $user->token = $token;
                
                unset($user->password);
                unset($user->status);
                
                DB::commit();
                
                return response()->json($user);
                
            }else{
                
                $user = $result[0];
                
                DB::beginTransaction();
                
                // SI EXISTE -> Verificar Estado
                if($user->status != 1){
                    // SI NO ESTA ACTIVO -> Lanzar error
                    return response()->json(['message' => 'Blocked account', 'status' => 401], 401);
                }else{
                    // SI ESTA ACTIVO -> Registrar Token
                    
                    // Actualizar googleid
                    DB::update('UPDATE users SET googleid=?, fullname=? where id=?', [$googleid, $fullname, $user->id]);
                    
                    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                        $photo = $request->file('photo');
                        DB::update('UPDATE users SET file=?, filetype=?, filesize=? where id=?', [file_get_contents($photo->getPathname()), $photo->getMimeType(), $photo->getSize(), $user->id]);
                    }
                    
                    // Get User Detail
                    $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status, IF(ISNULL(file), NULL, 1) as photo FROM users WHERE id=?", [$user->id]);
                    $user = $result[0];
                    
                    if($user->photo == 1){
                        $user->photo = '/people/'.$user->id.'/';
                    }
                    
                    // Recuperar preferencias
                    $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$user->id]);
                    $user->topics = $topics;
                    
                    // Registrar Token
                    $token = Hash::make( $email . ':' . $googleid . ':' . microtime() );    // (O MEJOR IMPLEMENTA OAUTH2 SERVER)
                    DB::insert('INSERT INTO tokens (users_id, token, lastupdate) VALUES (?, ?, now())', [$user->id, $token]);
                    $user->token = $token;
                    
                    unset($user->password);
                    unset($user->status);
                    
                    DB::commit();
                    
                    return response()->json($user);
                }
            }
            
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => 500], 500);
        }
    }

    /**
     * @SWG\Post(
     *  path="/api/users",
     *  tags={"Users"},
     *  summary="Update user profile",
     *  description="",
     *  @SWG\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @SWG\Schema()
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="Success message"
     *  ),
     * )
     */
    public function update(Request $request) 
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
        // Get User Detail
        $result = DB::select("SELECT id, email, googleid, fullname, latitude, longitude, status FROM users WHERE id=?", [$id]);
        
        if(count($result)==0)
            return response()->json(['message' => 'Record not found'], 404);
        
        $user = $result[0];
        
        // Recuperar preferencias
        $topics = DB::select("SELECT t.id, t.name FROM preferences p INNER JOIN topics t ON t.id = p.topics_id WHERE p.users_id = ?", [$id]);
        $user->topics = $topics;
        
        return response()->json($user);
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
        $list = DB::select("SELECT e.*, (select count(*) from attendants where users_id=1 and events_id=e.id) as registered FROM topics t
                            INNER JOIN `events` e ON e.topics_id=t.id
                            INNER JOIN preferences p ON p.topics_id=t.id
                            INNER JOIN users u ON u.id=p.users_id
                            WHERE u.id=?",  [$id]);
                            
        foreach($list as $event){
            $topics = DB::select("SELECT * FROM topics WHERE id=?", [$event->topics_id]);
            $event->topics = $topics[0];
            unset($event->topics_id);
            $event->image = '/uploads/images/events/'.$event->filename;
            
            $attendants = DB::select("SELECT * FROM attendants WHERE users_id=? AND events_id=?", [$id, $event->id]);
            if(count($attendants)!=0){
                $event->attendant = $attendants[0];
            }
        }                            
                            
        return response()->json($list);
    }
    
    /**
     * @SWG\Post(
     *  path="/api/users/{userid}/events/{eventid}",
     *  tags={"Users"},
     *  summary="Register into envent",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="userid",
     *      required=true,
     *      type="string"
     *  ),
     * @SWG\Parameter(
     *      in="path",
     *      name="eventid",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="Success message"
     *  ),
     * )
     */
    public function attendant($userid, $eventid)
    {
        
        DB::insert('INSERT INTO attendants (users_id, events_id, status) VALUES (?, ?, 1)', [$userid, $eventid]);                         
                            
        return response()->json(['message' => 'Success register']);
    }
    
    /**
     * @SWG\Put(
     *  path="/api/users/{userid}/events/{eventid}",
     *  tags={"Users"},
     *  summary="Checking into envent",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="userid",
     *      required=true,
     *      type="string"
     *  ),
     * @SWG\Parameter(
     *      in="path",
     *      name="eventid",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="Success message"
     *  ),
     * )
     */
    public function checking($userid, $eventid)
    {
        
        DB::update('UPDATE attendants SET status=2 WHERE users_id=? AND events_id=?', [$userid, $eventid]);                         
                            
        return response()->json(['message' => 'Success checking']);
    }
    
    /**
     * @SWG\Put(
     *  path="/api/users/{userid}/events/{eventid}/{rating}",
     *  tags={"Users"},
     *  summary="Rating envent",
     *  description="",
     *  @SWG\Parameter(
     *      in="path",
     *      name="userid",
     *      required=true,
     *      type="string"
     *  ),
     * @SWG\Parameter(
     *      in="path",
     *      name="eventid",
     *      required=true,
     *      type="string"
     *  ),
     *  @SWG\Response(
     *      response=200,
     *      description="Success message"
     *  ),
     * )
     */
    public function rating($userid, $eventid, $rating)
    {
        
        DB::update('UPDATE attendants SET rating=? WHERE users_id=? AND events_id=?', [$rating, $userid, $eventid]);                         
                            
        return response()->json(['message' => 'Success rating']);
    }
    
}