<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DashboardViewData;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardViewData $dashboardData)
    {
        return view('pages.admin.dashboard', [
            'dashboard' => $dashboardData->data(
                session('bbh_api_token'),
                $request->integer('birth_year') ?: null,
                (session('bbh_admin_user.role') ?? null) === 'super_admin'
            ),
        ]);
    }
}
