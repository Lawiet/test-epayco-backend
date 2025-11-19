<?php

namespace App\Services;

use App\Mail\PaymentConfirmationMail;
use App\Models\Client;
use App\Models\CodeConfirmation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

            $wallet = $client->wallet->update([
                'balance' => $client->wallet->balance + $valor,
            ]);

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

    /**
     * @param string $documento
     * @param string $celular
     * @param float $valor_compra
     * @return array
     */
    public function pagar($documento, $celular, $valor_compra)
    {
        if (empty($documento)) {
            return $this->formatResponse(false, '30', 'El campo "documento" es obligatorio para el pago.');
        }
        if (empty($celular)) {
            return $this->formatResponse(false, '31', 'El campo "celular" es obligatorio para el pago.');
        }
        if (!is_numeric($valor_compra) || empty($valor_compra)) {
            return $this->formatResponse(false, '32', 'El campo "valor_compra" es obligatorio y debe ser numérico.');
        }
        $valor_compra = round((float)$valor_compra, 2);

        if ($valor_compra <= 0) {
            return $this->formatResponse(false, '33', 'El valor de la compra debe ser positivo.');
        }

        $client = Client::where('identification', $documento)
            ->where('phone', $celular)
            ->first();

        if (!$client || !$client->wallet) {
            return $this->formatResponse(false, '34', 'Cliente o billetera no encontrados (documento y celular no coinciden).');
        }

        $wallet = $client->wallet;

        if ($wallet->balance < $valor_compra) {
            return $this->formatResponse(false, '35', 'Saldo insuficiente para realizar el pago. Saldo actual: ' . $wallet->balance);
        }

        try {
            DB::beginTransaction();

            $reference = 'BUY_' . Str::uuid();
            $transaction = $client->wallet->transactions()->create([
                'type' => 'buy',
                'amount' => $valor_compra,
                'status' => 'pending',
                'reference' => $reference,
            ]);

            $sessionId = (string)Str::uuid();
            $token = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $transaction->confirmations()->create([
                'transaction_id' => $transaction->id,
                'code' => $token,
                'expires_at' => now()->addMinutes(5),
                'used' => false,
                'session_id' => $sessionId,
            ]);

            Mail::to($client->email)->send(new PaymentConfirmationMail($token, $valor_compra, $client->name));

            DB::commit();

            return $this->formatResponse(true, '00', 'Token de confirmación generado y "enviado" al email: ' . $client->email . '. Use el id de sesión y el token para la confirmación.', [
                'id_sesion' => $sessionId,
                'referencia' => $reference
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->formatResponse(false, '99', 'Error interno del sistema al iniciar el pago: ' . $e->getMessage());
        }
    }

    /**
     * @param string $id_sesion
     * @param string $token
     * @return array
     */
    public function confirmarPago($id_sesion, $token)
    {
        if (empty($id_sesion)) {
            return $this->formatResponse(false, '40', 'El campo "id_sesion" es obligatorio.');
        }
        if (empty($token)) {
            return $this->formatResponse(false, '41', 'El campo "token" es obligatorio.');
        }

        $confirmation = CodeConfirmation::with(['transaction.wallet'])
            ->where('session_id', $id_sesion)
            ->where('code', $token)
            ->first();

        if (!$confirmation || !$confirmation->transaction || !$confirmation->transaction->wallet) {
            return $this->formatResponse(false, '42', 'Token o ID de sesión inválido o no encontrado.');
        }

        $transaction = $confirmation->transaction;
        $wallet = $transaction->wallet;
        $amount = $transaction->amount;

        if ($confirmation->used) {
            return $this->formatResponse(false, '44', 'La sesión ya ha sido utilizada para confirmar el pago.');
        }

        if (now()->greaterThan($confirmation->expires_at)) {
            $confirmation->update([
                'used' => true,
            ]);
            $transaction->update([
                "status" => 'failed',
            ]);
            return $this->formatResponse(false, '43', 'El token de confirmación ha expirado.');
        }

        if ($transaction->status !== 'pending') {
            return $this->formatResponse(false, '44', 'La transacción ya fue procesada (completada o fallida).');
        }

        if ($wallet->balance < $amount) {
            $confirmation->update([
                'used' => true,
            ]);
            $transaction->update([
                "status" => 'failed',
            ]);
            return $this->formatResponse(false, '45', 'Saldo insuficiente para completar la compra.');
        }

        try {
            DB::beginTransaction();

            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            $transaction->update([
                "status" => 'completed',
            ]);

            $confirmation->update([
                'used' => true,
            ]);

            DB::commit();

            return $this->formatResponse(true, '00', 'Pago confirmado y saldo descontado exitosamente.', [
                'saldo_actual' => $wallet->balance,
                'valor_descontado' => $amount,
                'referencia' => $transaction->reference
            ]);

        } catch (Exception $e) {
            \Log::error($e);
            DB::rollBack();
            if ($transaction->status === 'pending') {
                $transaction->status = 'failed';
                $transaction->save();
            }
            return $this->formatResponse(false, '99', 'Error interno del sistema al confirmar el pago: ' . $e->getMessage());
        }
    }

    /**
     * @param string $documento
     * @param string $celular
     * @return array
     */
    public function consultarSaldo($documento, $celular)
    {
        if (empty($documento)) {
            return $this->formatResponse(false, '50', 'El campo "documento" es obligatorio para la consulta.');
        }
        if (empty($celular)) {
            return $this->formatResponse(false, '51', 'El campo "celular" es obligatorio para la consulta.');
        }

        $client = Client::where('identification', $documento)
            ->where('phone', $celular)
            ->first();

        if (!$client || !$client->wallet) {
            return $this->formatResponse(false, '52', 'Cliente o billetera no encontrados (documento y celular no coinciden).');
        }

        $wallet = $client->wallet;

        return $this->formatResponse(true, '00', 'Consulta de saldo exitosa.', [
            'saldo_actual' => (float)$wallet->balance,
        ]);
    }
}
