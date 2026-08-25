<?php

namespace App\Http\Controllers;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Models\Recommendation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $recommendations = Recommendation::query()
            ->with(['vehicle', 'driver', 'alert'])
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('generated_at')
            ->paginate(20)
            ->withQueryString();

        return view('recommendations.index', [
            'recommendations' => $recommendations,
            'priorities' => RecommendationPriority::cases(),
            'statuses' => RecommendationStatus::cases(),
        ]);
    }

    public function update(Request $request, Recommendation $recommendation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(RecommendationStatus::class)],
        ]);

        $recommendation->update(['status' => $data['status']]);

        return back()->with('status', 'Recommendation status updated.');
    }
}
