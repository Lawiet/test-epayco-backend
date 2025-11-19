<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v1/wallet'], function () use ($router) {
    $router->post('register', 'WalletController@registroCliente');
    $router->post('recharge', 'WalletController@recargaBilletera');
    $router->post('pay', 'WalletController@pagar');
    $router->post('confirm-pay', 'WalletController@confirmarPago');
});
