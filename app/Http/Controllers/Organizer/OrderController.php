<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Los pagos del organizador: qué compró, cuándo y en qué quedó.
 *
 * Se entra desde Configuración, que es donde están los datos de su cuenta.
 *
 * No va detrás de 'event.active' a propósito: cuando una invitación vence es
 * justo cuando alguien quiere revisar qué pagó, y dejarlo fuera de su propio
 * historial sería lo contrario de lo que necesita.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::with('template')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('organizer.orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        /*
         * Un pago es de quien lo hizo. Se responde 404 y no 403 para no
         * confirmarle a nadie que esa orden existe.
         */
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('template', 'event');

        return view('organizer.orders.show', ['order' => $order]);
    }
}
