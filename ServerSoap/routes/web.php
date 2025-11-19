<?php

use App\Services\WalletSoapService;
use Illuminate\Support\Facades\Route;
use Laminas\Soap\AutoDiscover;
use Laminas\Soap\Server;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('soap')->group(function () {
    Route::get('wallet.wsdl', function () {
        $externalHost = env('SOAP_EXTERNAL_HOST', env('APP_URL'));
        $soapUri = $externalHost . '/soap/server';

        $autodiscover = new AutoDiscover();
        $autodiscover->setClass(WalletSoapService::class)
            ->setUri($soapUri);

        header('Content-Type: text/xml');
        echo $autodiscover->toXml();
        exit;
    })->name('soap.wsdl');

    Route::post('server', function () {
        ini_set('soap.wsdl_cache_enabled', 0);
        ini_set('soap.wsdl_cache_ttl', 0);

        $externalHost = env('SOAP_EXTERNAL_HOST') ?? route('soap.wsdl', [], false);
        $wsdlUri = $externalHost . '/soap/wallet.wsdl';
        $server = new Server($wsdlUri);
        $server->setClass(WalletSoapService::class);

        header('Content-Type: text/xml; charset=utf-8');

        $server->handle();

        exit;
    })->name('soap.server');
});
