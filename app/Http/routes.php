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

$app->get('/', function () use ($app) {
    //return $app->version();
    return redirect('/api/documentation');
});

$app->get('/api/topics', 'TopicController@topics');

$app->get('/api/events', 'EventController@events');

$app->post('/api/login', 'UserController@login');

$app->post('/api/users', 'UserController@register');

$app->get('/api/users/{id}', 'UserController@user');

$app->get('/api/users/{id}/topics', 'UserController@topics');

$app->get('/api/users/{id}/events', 'UserController@events');
