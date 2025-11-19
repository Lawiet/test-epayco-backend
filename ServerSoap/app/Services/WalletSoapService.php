<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Wallet;
use Exception;

class WalletSoapService
{
    private function formatResponse($success, $cod_error, $message_error, $data = [])
    {
        return [
            'success'       => $success,
            'cod_error'     => $cod_error,
            'message_error' => $message_error,
            'data'          => $data,
        ];
    }

    /**
     * @param string $documento
     * @param string $nombres
     * @param string $email
     * @param string $celular
     * @return array
     */
    public function registroCliente($documento, $nombres, $email, $celular)
    {
        if (empty($documento)) {
            return $this->formatResponse(false, '10', 'El campo "documento" es obligatorio.');
        }
        if (empty($nombres)) {
            return $this->formatResponse(false, '11', 'El campo "nombres" es obligatorio.');
        }
        if (empty($email)) {
            return $this->formatResponse(false, '12', 'El campo "email" es obligatorio.');
        }
        if (empty($celular)) {
            return $this->formatResponse(false, '13', 'El campo "celular" es obligatorio.');
        }
        if (Client::where('identification', $documento)->exists()) {
            return $this->formatResponse(false, '01', 'El documento ya está registrado.');
        }

        if (Client::where('email', $email)->exists()) {
            return $this->formatResponse(false, '02', 'El email ya está registrado.');
        }

        try {
            $client = Client::create([
                'name' => $nombres,
                'email' => $email,
                'identification' => $documento,
                'phone' => $celular,
            ]);

            $client->wallets()->create([]);

            return $this->formatResponse(true, '00', 'Cliente y billetera registrados exitosamente.');
        } catch (Exception $e) {
            return $this->formatResponse(false, '99', 'Error al registrar el cliente: ' . $e->getMessage());
        }
    }
}
