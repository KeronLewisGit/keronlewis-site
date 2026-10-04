<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The private page a client is sent to write a testimonial. Only someone
 * holding the link's token can reach it, and each link takes one submission.
 */
class TestimonialController extends Controller
{
    public function create(Testimonial $testimonial): View
    {
        return view('testimonials.create', [
            'profile' => config('portfolio.profile'),
            'testimonial' => $testimonial,
        ]);
    }

    public function store(Request $request, Testimonial $testimonial): RedirectResponse
    {
        if ($testimonial->submitted_at === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'role' => ['nullable', 'string', 'max:160'],
                'quote' => ['required', 'string', 'min:20', 'max:600'],
                'consent' => ['accepted'],
            ], [
                'name.required' => 'Please add your name.',
                'quote.required' => 'The testimonial is empty.',
                'quote.min' => 'Could you add a little more detail?',
                'consent.accepted' => 'Please confirm that this can be published.',
            ]);

            $testimonial->update([
                'name' => $data['name'],
                'role' => $data['role'] ?? null,
                'quote' => $data['quote'],
                'submitted_at' => now(),
            ]);
        }

        return redirect()->route('testimonials.create', $testimonial->token);
    }
}
