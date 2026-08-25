<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $alerts = Alert::query()
            ->with(['vehicle', 'driver'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->string('severity')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('triggered_at')
            ->paginate(20)
            ->withQueryString();

        return view('alerts.index', [
            'alerts' => $alerts,
            'types' => AlertType::cases(),
            'severities' => AlertSeverity::cases(),
            'statuses' => AlertStatus::cases(),
        ]);
    }

    public function update(Request $request, Alert $alert): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,acknowledged,resolved'],
        ]);

        $alert->status = $data['status'];
        $alert->resolved_at = $data['status'] === AlertStatus::Resolved->value ? now() : null;
        $alert->save();

        return back()->with('status', 'Alert status updated.');
    }
}
