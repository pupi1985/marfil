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

// For the console application

$app->post('/crack', 'MarfilController@createConsoleCrackRequest');
$app->post('/work', 'MarfilController@workRequest');
$app->post('/result', 'MarfilController@resultRequest');

$app->get('/download-cap/{id}', 'MarfilController@downloadCapRequest');
$app->get('/download-part/{hash}/{id}', 'MarfilController@downloadPartRequest');

// For the web interface

$app->get('/', 'MarfilController@showCrackRequestsInformation');
$app->post('/', 'MarfilController@createWebCrackRequest');

$app->delete('/crack-request/all', 'MarfilController@deleteAllCrackRequests');
$app->delete('/crack-request/{id}', 'MarfilController@deleteCrackRequest');
