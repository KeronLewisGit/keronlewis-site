<?php

namespace App\Http\Controllers;

use App\Support\BookingCalendar;
use App\Support\Portfolio;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __invoke(Portfolio $portfolio, BookingCalendar $calendar, string $slug): View
    {
        $services = $portfolio->services();
        $service = $services->firstWhere('slug', $slug);

        abort_if($service === null, 404);

        $projects = $portfolio->projects();

        return view('services.show', [
            'profile' => $portfolio->profile(),
            'service' => $service,
            'page' => $service['page'],
            'projects' => collect($service['page']['projects'] ?? [])
                ->map(fn (string $project) => $projects->firstWhere('slug', $project))
                ->filter()
                ->values(),
            'others' => $services->where('slug', '!==', $slug)->values(),
            'testimonials' => $portfolio->testimonials()->take(2),
            'bookingOpen' => $calendar->isEnabled(),
            'schema' => $portfolio->serviceSchema($service),
        ]);
    }
}
