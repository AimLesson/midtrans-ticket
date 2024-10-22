<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

class MidtransPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::create([
            'invoice_number' => $request->invoice_number,
            'amount' => $request->amount,
            'status' => 'CREATED',
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->withBasicAuth(env('MIDTRANS_SERVER_KEY'), '')
            ->post('https://api.sandbox.midtrans.com/v2/charge', [
                'payment_type' => 'gopay',
                'transaction_details' => [
                    'order_id' => $transaction->id,
                    'gross_amount' => $transaction->amount,
                ],
            ]);

        if ($response->status() == 201 || $response->status() == 200) {
            $actions = $response->json('actions');
            if (empty($actions)) {
                return response()->json([
                    'message' => $response->json('status_message'),
                    'data' => $transaction,
                ], 500);
            }

            $actionMap = [];
            foreach ($actions as $action) {
                $actionMap[$action['name']] = $action['url'];
            }

            $qrCodeUrl = $actionMap['generate-qr-code'];
            app(SendQrCodeController::class)->__invoke(new Request(['qr_code_url' => $qrCodeUrl]));

            return response()->json(['qr' => $qrCodeUrl]);
        }

        return response()->json(['message' => $response->body()], 500);
    }
}
