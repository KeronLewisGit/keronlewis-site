<?php

namespace App\Http\Controllers;

use App\Support\Portfolio;

class HomeController extends Controller
{
    public function __invoke(Portfolio $portfolio)
    {
        return view('home', [
            'profile' => $portfolio->profile(),
            'projects' => $portfolio->projects(),
            'projectGroups' => $portfolio->projectGroups(),
            'experience' => $portfolio->experience(),
            'skills' => $portfolio->skills(),
            'skillIndex' => $portfolio->skillIndex(),
            'education' => config('portfolio.education'),
            'certifications' => config('portfolio.certifications'),
            'topics' => config('portfolio.contact_topics'),
            'services' => config('portfolio.services'),
        ]);
    }
}
