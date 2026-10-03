<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleAnalytics;
use App\Services\GoogleAnalyticsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConnectionController extends Controller
{
    public function edit()
    {
        $analytics = GoogleAnalytics::fromSettings();

        return view('admin.connection', [
            'measurementId' => Setting::read('ga_measurement_id'),
            'searchConsole' => Setting::read('google_site_verification'),
            'propertyId' => $analytics->propertyId(),
            'serviceAccount' => $analytics->serviceAccountEmail(),
            'connected' => $analytics->isConnected(),
        ]);
    }

    public function update(Request $request)
    {
        // Accept either the bare Search Console code or the whole <meta> tag Google gives you.
        $verification = trim((string) $request->input('search_console'));
        if (preg_match('/content="([^"]+)"/', $verification, $match)) {
            $verification = $match[1];
        }

        $request->merge([
            'measurement_id' => strtoupper(trim((string) $request->input('measurement_id'))) ?: null,
            'search_console' => $verification ?: null,
        ]);

        $data = $request->validate([
            'measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]{4,20}$/'],
            'search_console' => ['nullable', 'regex:/^[A-Za-z0-9_-]{20,100}$/'],
            'property_id' => ['nullable', 'digits_between:5,15', 'required_with:credentials'],
            'credentials' => ['nullable', 'file', 'max:32'],
        ], [
            'measurement_id.regex' => 'A measurement ID looks like G-AB12CD34EF.',
            'search_console.regex' => "That doesn't look like a Search Console code. Paste the HTML tag Google shows, or just the long code inside it.",
            'property_id.digits_between' => 'The Property ID is a number, such as 412345678.',
            'property_id.required_with' => 'Enter the Property ID that this key should read.',
        ]);

        Setting::write('ga_measurement_id', $data['measurement_id']);
        Setting::write('google_site_verification', $data['search_console'] ?? null);

        if (blank($data['property_id'] ?? null)) {
            // Saving with the Property ID cleared only updates the tracking snippet.
            return redirect()->route('admin.connection')->with('status', 'Saved.');
        }

        $credentials = $request->hasFile('credentials')
            ? $this->parseKey($request->file('credentials')->get())
            : json_decode((string) Setting::read('ga_credentials'), true);

        if (! $credentials) {
            throw ValidationException::withMessages(['credentials' => 'Upload the service-account JSON key.']);
        }

        // Prove the connection works before replacing whatever is saved.
        try {
            (new GoogleAnalytics($data['property_id'], $credentials))->ping();
        } catch (GoogleAnalyticsException $e) {
            throw ValidationException::withMessages(['property_id' => $e->getMessage()]);
        }

        Setting::write('ga_property_id', $data['property_id']);
        Setting::write('ga_credentials', json_encode($credentials));
        AnalyticsController::forgetReports();

        return redirect()->route('admin.analytics')->with('status', 'Google Analytics is connected.');
    }

    public function destroy()
    {
        Setting::write('ga_property_id', null);
        Setting::write('ga_credentials', null);
        AnalyticsController::forgetReports();

        return redirect()->route('admin.connection')->with('status', 'Disconnected. The saved key has been deleted.');
    }

    /**
     * Keep only the fields needed to sign requests.
     */
    private function parseKey(string $json): array
    {
        $key = json_decode($json, true);

        if (! is_array($key) || ($key['type'] ?? null) !== 'service_account' || blank($key['client_email'] ?? null) || blank($key['private_key'] ?? null)) {
            throw ValidationException::withMessages([
                'credentials' => "That file isn't a service-account key. It should be the JSON file Google Cloud downloads when you create a key.",
            ]);
        }

        return [
            'client_email' => $key['client_email'],
            'private_key' => $key['private_key'],
            'private_key_id' => $key['private_key_id'] ?? '',
        ];
    }
}
