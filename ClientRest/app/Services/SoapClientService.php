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

    public function recargaBilletera(string $documento, string $celular, float $valor): array
    {
        $params = [
            'documento' => $documento,
            'celular' => $celular,
            'valor' => $valor
        ];

        return $this->_call('recargaBilletera', $params);
    }

    public function pagar(string $documento, string $celular, float $valor_compra): array
    {
        $params = [
            'documento' => $documento,
            'celular' => $celular,
            'valor_compra' => $valor_compra,
        ];

        return $this->_call('pagar', $params);
    }

    public function confirmarPago(string $id_sesion, string $token): array
    {
        $params = [
            'id_sesion' => $id_sesion,
            'token' => $token,
        ];

        return $this->_call('confirmarPago', $params);
    }
}
