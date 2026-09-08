<?php

declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use Core\Controller;
use Modules\Dashboard\Models\Stats;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('Dashboard::index', [
            'title'      => t('dash.title'),
            'status'     => Stats::unitStatusCounts(),
            'occupancy'  => Stats::occupancyRate(),
            'rentRoll'   => Stats::monthlyRentRoll(),
            'collected'  => Stats::collectedThisMonth(),
            'arrears'    => Stats::outstandingArrears(),
            'tickets'    => Stats::openTickets(),
            'recovery'   => Stats::recoveryCases(),
            'payments'   => Stats::recentPayments(),
            'channels'   => Stats::paymentChannelsThisMonth(),
            'collections' => Stats::monthlyCollections(6),
            'aging'      => Stats::arrearsAging(),
        ]);
    }
}
