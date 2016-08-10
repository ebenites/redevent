<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * 
 * https://github.com/DarkaOnLine/SwaggerLume   (Solo soporta HTTP y no HTTPS)
 * Cada cambio en los tags correr: php artisan swagger-lume:generate 
 * Example: https://github.com/zircote/swagger-php/blob/master/Examples/petstore.swagger.io/controllers/PetController.php
 * Example running: http://petstore.swagger.io/
 * Specification: https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md
 * 
 * /

/**
 * @SWG\Swagger(
 *     schemes={"http"},
 *     host="eventos-ebenites.c9users.io",
 *     basePath="/",
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="RedEvent API",
 *         description="Servicio RestFul para el aplicativo móvil RedEvent.",
 *         @SWG\Contact(
 *             email="erick.benites@gmail.com"
 *         )
 *     )
 * )
 */
 
$app->get('/', function () use ($app) {
    //return $app->version();
    return redirect('/api/documentation');
});

/**
 *  TopicController
 */

//$app->get('/api/topics', ['middleware' => 'auth', 'uses' => 'TopicController@topics']); // Mejor usar "public function __construct(){ $this->middleware('auth'); }" en el controlador
$app->get('/api/topics', 'TopicController@topics');

/**
 *  UserController 
 */
 
$app->post('/api/login', 'UserController@login');

$app->post('/api/glogin', 'UserController@login_with_google');

$app->post('/api/users/{id}/topics', 'UserController@update_topics');

$app->post('/api/users/{id}/photo', 'UserController@upload_photo');

$app->get('/api/users/{id}/events', 'UserController@events');

$app->get('/api/users/{id}/myevents', 'UserController@myevents');

$app->post('/api/users/{userid}/events/{eventid}', 'UserController@attendant');

$app->put('/api/users/{userid}/events/{eventid}', 'UserController@checking');

$app->put('/api/users/{userid}/events/{eventid}/{rating}', 'UserController@rating');



$app->get('/api/users/{id}', 'UserController@user');

$app->put('/api/users/{id}', 'UserController@update');

$app->get('/api/events', 'EventController@events');

$app->get('/api/users/{id}/topics', 'UserController@topics');


