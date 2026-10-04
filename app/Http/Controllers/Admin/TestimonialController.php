<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.testimonials', [
            'pending' => Testimonial::pending()->latest('submitted_at')->get(),
            'unanswered' => Testimonial::unanswered()->latest()->get(),
            'approved' => Testimonial::approved()->latest('approved_at')->get(),
            'projects' => collect(config('portfolio.projects'))->pluck('name'),
        ]);
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.testimonial', [
            'testimonial' => $testimonial,
            'projects' => collect(config('portfolio.projects'))->pluck('name'),
        ]);
    }

    /**
     * Correct the name, project or wording, and choose the phrases to highlight.
     */
    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $submitted = $testimonial->submitted_at !== null;

        $data = $request->validate([
            'sent_to' => ['required', 'string', 'max:120'],
            'project' => ['nullable', 'string', 'max:160'],
            'quote' => [$submitted ? 'required' : 'exclude', 'string', 'min:20', 'max:600'],
            'highlights' => [$submitted ? 'nullable' : 'exclude', 'string', 'max:600'],
        ], [
            'sent_to.required' => "Add the client's name.",
            'quote.required' => 'The testimonial is empty.',
        ]);

        $changes = ['sent_to' => $data['sent_to']] + Testimonial::projectFields($data['project'] ?? null);

        if ($submitted) {
            $highlights = collect(preg_split('/\R/', (string) ($data['highlights'] ?? '')))->map(fn (string $line) => trim($line))->filter()->unique()->values();

            // A highlight only works if it is in the testimonial word for word.
            foreach ($highlights as $phrase) {
                if (mb_stripos($data['quote'], $phrase) === false) {
                    throw ValidationException::withMessages(['highlights' => "\"{$phrase}\" isn't in the testimonial. Copy the phrase exactly as it's written."]);
                }
            }

            $changes += ['quote' => $data['quote'], 'highlights' => $highlights->all() ?: null];
        }

        $testimonial->update($changes);

        return redirect()->route('admin.testimonials')->with('status', "Saved the changes to {$testimonial->sent_to}'s testimonial.");
    }

    /**
     * Create a private link to send to one client.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sent_to' => ['required', 'string', 'max:120'],
            'project' => ['nullable', 'string', 'max:160'],
        ], [
            'sent_to.required' => "Add the client's name. It is shown beside their testimonial.",
        ]);

        Testimonial::invite($data['sent_to'], $data['project'] ?? null);

        return redirect()->route('admin.testimonials')->with('status', "Link created for {$data['sent_to']}. Copy it below and send it to them.");
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        abort_if($testimonial->submitted_at === null, 404);

        $testimonial->update(['approved_at' => now()]);

        return redirect()->route('admin.testimonials')->with('status', "Published the testimonial from {$testimonial->sent_to}.");
    }

    public function unpublish(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['approved_at' => null]);

        return redirect()->route('admin.testimonials')->with('status', "Took the testimonial from {$testimonial->sent_to} off the site.");
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials')->with('status', 'Deleted.');
    }
}
