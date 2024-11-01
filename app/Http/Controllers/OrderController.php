<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function create()
    {
        return view('order.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'nomer_whatsapp' => 'required|string|max:15',
            'email' => 'required|string|email|max:255',
            'price_range' => 'required|integer',
        ]);

        $user = User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->nama,
                'alamat' => $request->alamat,
                'nomer_whatsapp' => $request->nomer_whatsapp,
            ]
        );

        $order = Order::create([
            'user_id' => $user->id,
            'price_range' => $request->price_range,
        ]);

        // return redirect()->route('process-payment', ['order_id' => $order->id]);

        // return view('order.process-payment', ['order_id' => $order->id]);

        return view('order.process-payment', [
            'order_id' => $order->id,
            'amount' => $request->price_range,
        ]);
    }
}
