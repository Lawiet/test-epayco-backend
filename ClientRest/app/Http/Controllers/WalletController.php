<?php

namespace App\Http\Controllers;

use App\Services\SoapClientService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $soapClient;

    public function __construct(SoapClientService $soapClient)
    {
        $this->soapClient = $soapClient;
    }

    /**
     * Registra un cliente llamando al método SOAP.
     */
    public function registroCliente(Request $request)
    {
        $this->validate($request, [
            'documento' => 'required|string|max:20',
            'nombres'   => 'required|string|max:255',
            'email'     => 'required|email', // Nota: La validación unique debe ocurrir en el SOAP/BD
            'celular'   => 'required|string|max:255',
        ]);

        try {
            $response = $this->soapClient->registroCliente(
                $request->input('documento'),
                $request->input('nombres'),
                $request->input('email'),
                $request->input('celular')
            );

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cod_error' => '99',
                'message_error' => 'Error de puente/comunicación: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Recarga la billetera llamando al método SOAP.
     */
    public function recargaBilletera(Request $request)
    {
        $this->validate($request, [
            'documento' => 'required|string',
            'celular'   => 'required|string',
            'valor'     => 'required|numeric|min:1',
        ]);

        try {
            $response = $this->soapClient->recargaBilletera(
                $request->input('documento'),
                $request->input('celular'),
                (float) $request->input('valor')
            );
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cod_error' => '99',
                'message_error' => 'Error de puente/comunicación: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Inicia el proceso de pago, genera token y id_sesion (método SOAP).
     */
    public function pagar(Request $request)
    {
        $this->validate($request, [
            'documento'    => 'required|string',
            'celular'      => 'required|string',
            'valor_compra' => 'required|numeric|min:1',
        ]);

        try {
            $response = $this->soapClient->pagar(
                $request->input('documento'),
                $request->input('celular'),
                (float) $request->input('valor_compra')
            );
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cod_error' => '99',
                'message_error' => 'Error de puente/comunicación: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Confirma el pago usando id_sesion y token (método SOAP).
     */
    public function confirmarPago(Request $request)
    {
        $this->validate($request, [
            'id_sesion' => 'required|string',
            'token'     => 'required|string|size:6',
        ]);

        try {
            $response = $this->soapClient->confirmarPago(
                $request->input('id_sesion'),
                $request->input('token')
            );
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cod_error' => '99',
                'message_error' => 'Error de puente/comunicación: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Consulta el saldo de la billetera (método SOAP).
     */
    public function consultarSaldo(Request $request)
    {
        $this->validate($request, [
            'documento' => 'required|string',
            'celular'   => 'required|string',
        ]);

        try {
            $response = $this->soapClient->consultarSaldo(
                $request->input('documento'),
                $request->input('celular')
            );
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cod_error' => '99',
                'message_error' => 'Error de puente/comunicación: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
