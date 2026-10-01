<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\DashboardPeriod;
use App\Services\Superadmin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        $period = DashboardPeriod::fromRequest(
            $request->query('periodo'),
            $request->query('desde'),
            $request->query('hasta'),
        );

        return view('superadmin.dashboard', [
            'period' => $period,
            // Estos dos miran el periodo elegido...
            'sales' => $this->dashboard->sales($period),
            'activity' => $this->dashboard->activity($period),
            // ...y estos dos son la foto de hoy.
            'invitations' => $this->dashboard->invitations(),
            'pending' => $this->dashboard->pending(),
        ]);
    }
}
