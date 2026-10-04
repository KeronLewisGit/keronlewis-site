<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.testimonials', [
            'pending' => Testimonial::pending()->latest('submitted_at')->get(),
            'unanswered' => Testimonial::unanswered()->latest()->get(),
            'approved' => Testimonial::approved()->latest('approved_at')->get(),
            'projects' => collect(config('portfolio.projects'))->pluck('name', 'slug'),
        ]);
    }

    /**
     * Create a private link to send to one client.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sent_to' => ['required', 'string', 'max:120'],
            'project_slug' => ['nullable', Rule::in(collect(config('portfolio.projects'))->pluck('slug'))],
        ], [
            'sent_to.required' => 'Say who the link is for, so you can tell the links apart.',
        ]);

        Testimonial::invite($data['sent_to'], $data['project_slug'] ?? null);

        return redirect()->route('admin.testimonials')->with('status', "Link created for {$data['sent_to']}. Copy it below and send it to them.");
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        abort_if($testimonial->submitted_at === null, 404);

        $testimonial->update(['approved_at' => now()]);

        return redirect()->route('admin.testimonials')->with('status', "Published the testimonial from {$testimonial->name}.");
    }

    public function unpublish(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['approved_at' => null]);

        return redirect()->route('admin.testimonials')->with('status', "Took the testimonial from {$testimonial->name} off the site.");
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials')->with('status', 'Deleted.');
    }
}
