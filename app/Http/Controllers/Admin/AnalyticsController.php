<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleAnalytics;
use App\Services\GoogleAnalyticsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 28);
        $days = in_array($days, GoogleAnalytics::RANGES) ? $days : 28;

        $analytics = GoogleAnalytics::fromSettings();
        $shared = ['days' => $days, 'ranges' => GoogleAnalytics::RANGES, 'propertyId' => $analytics->propertyId()];

        if (! $analytics->isConnected()) {
            return view('admin.analytics', $shared + ['state' => 'disconnected', 'tracking' => Setting::measurementId()]);
        }

        try {
            // Google's quotas are per property, so reports are kept for ten minutes.
            $report = Cache::remember("ga.overview.{$days}", 600, fn () => $analytics->overview($days));
            $activeNow = Cache::remember('ga.active_now', 60, fn () => $analytics->activeNow());
        } catch (GoogleAnalyticsException $e) {
            return view('admin.analytics', $shared + ['state' => 'error', 'error' => $e->getMessage()]);
        }

        return view('admin.analytics', $shared + ['state' => 'ready', 'report' => $report, 'activeNow' => $activeNow]);
    }

    public function refresh(Request $request)
    {
        static::forgetReports();

        return redirect()->route('admin.analytics', $request->only('days'));
    }

    public static function forgetReports(): void
    {
        foreach (GoogleAnalytics::RANGES as $days) {
            Cache::forget("ga.overview.{$days}");
        }
        Cache::forget('ga.active_now');
    }
}
