<?php

namespace App\Services;

use Exception;
use SoapClient;

class SoapClientService
{
    protected $client;
    protected $wsdl;

    public function __construct(string $wsdl)
    {
        $this->wsdl = $wsdl;

        try {
            $this->client = new SoapClient($this->wsdl, [
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
        } catch (Exception $e) {
            throw new Exception("Error al conectar con el servicio SOAP: " . $e->getMessage());
        }
    }

    private function _call($method, $params)
    {
        $response = $this->client->__soapCall($method, $params);

        return json_decode(json_encode($response), true);
    }

    public function registroCliente(string $documento, string $nombres, string $email, string $celular): array
    {
        $params = [
            'documento' => $documento,
            'nombres' => $nombres,
            'email' => $email,
            'celular' => $celular,
        ];

        return $this->_call('registroCliente', $params);
    }
}
