<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\PaymentRequest;

class PayController extends Controller
{
    public function payEasyMoney(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric',
                'currency' => 'required|string'
            ]);

            if (floor($validated['amount']) != $validated['amount']) {
                throw new \Exception('EasyMoney no acepta montos decimales');
            }

            $paymentRequest = PaymentRequest::create([
                'provider' => 'EasyMoney',
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'request_data' => json_encode($validated)
            ]);

            $response = Http::post('http://localhost:3000/process', [
                'amount' => (int)$validated['amount'],
                'currency' => $validated['currency']
            ]);

            $responseData = $response->json();
            
            $isError = $response->body() === 'error' || 
                      (is_array($responseData) && isset($responseData['error'])) ||
                      !$response->successful();

            $transaction = Transaction::create([
                'payment_request_id' => $paymentRequest->id,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'status' => $response->successful() ? 'completed' : 'failed',
                'provider' => 'EasyMoney',
                'response_data' => $response->body() === 'error' ? ['error' => 'Invalid amount'] : $responseData
            ]);

            if ($isError) {
                throw new \Exception('Error en el procesamiento del pago: ' . $response->body());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado correctamente',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function paySuperWalletz(Request $request)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3'
            ]);

            $callbackUrl = url('/api/webhook/super-walletz');

            $paymentRequest = PaymentRequest::create([
                'provider' => 'SuperWalletz',
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'request_data' => json_encode([
                    'amount' => $validated['amount'],
                    'currency' => strtoupper($validated['currency']),
                    'callback_url' => $callbackUrl
                ])
            ]);

            $response = Http::post('http://localhost:3003/pay', [
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'callback_url' => $callbackUrl
            ]);

            $responseData = $response->json();

            if (!$response->successful() || !isset($responseData['success'])) {
                throw new \Exception('Error en el procesamiento del pago');
            }

            $transaction = Transaction::create([
                'payment_request_id' => $paymentRequest->id,
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'status' => 'pending',
                'provider' => 'SuperWalletz',
                'response_data' => $responseData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago iniciado correctamente',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'provider_transaction_id' => $responseData['transaction_id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function handleSuperWalletzWebhook(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string',
                'status' => 'required|string',
                'amount' => 'required|numeric',
                'currency' => 'required|string'
            ]);

            $webhookData = $request->all();
            
            $transaction = Transaction::where('amount', $validated['amount'])
                                   ->where('currency', strtoupper($validated['currency']))
                                   ->where('status', 'pending')
                                   ->where('provider', 'SuperWalletz')
                                   ->latest()
                                   ->first();

            if (!$transaction) {
                throw new \Exception('Transacción no encontrada');
            }

            $transaction->update([
                'status' => $validated['status'] === 'success' ? 'completed' : 'failed',
                'response_data' => array_merge(
                    $transaction->response_data ?? [],
                    ['webhook_data' => $webhookData]
                )
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook procesado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
