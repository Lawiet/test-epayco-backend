<?php

namespace App\Services;

use App\Models\Client;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletSoapService
{
    private function formatResponse($success, $cod_error, $message_error, $data = [])
    {
        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => $data,
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

    /**
     * @param string $documento
     * @param string $celular
     * @param float $valor
     * @return array
     */
    public function recargaBilletera($documento, $celular, $valor)
    {
        if (empty($documento)) {
            return $this->formatResponse(false, '20', 'El campo "documento" es obligatorio para la recarga.');
        }
        if (empty($celular)) {
            return $this->formatResponse(false, '21', 'El campo "celular" es obligatorio para la recarga.');
        }
        if (!is_numeric($valor) || empty($valor)) {
            return $this->formatResponse(false, '22', 'El campo "valor" es obligatorio y debe ser numérico.');
        }
        $valor = round((float)$valor, 2);

        if ($valor <= 0) {
            return $this->formatResponse(false, '23', 'El valor a recargar debe ser positivo.');
        }

        $client = Client::where('identification', $documento)
            ->where('phone', $celular)
            ->first();

        if (!$client || !$client->wallet) {
            return $this->formatResponse(false, '24', 'Cliente o billetera no encontrados (documento y celular no coinciden).');
        }

        try {
            DB::beginTransaction();

            $wallet = $client->wallet;
            $wallet->balance += $valor;
            $wallet->save();

            $reference = 'DEP_' . uniqid(true);
            $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $valor,
                'status' => 'completed',
                'reference' => $reference,
            ]);

            DB::commit();

            return $this->formatResponse(true, '00', 'Recarga exitosa.', [
                'saldo_actual' => $wallet->balance,
                'referencia' => $reference
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->formatResponse(false, '99', 'Error interno del sistema al procesar la recarga: ' . $e->getMessage());
        }
    }
}
