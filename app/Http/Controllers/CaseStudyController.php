<?php

namespace App\Http\Controllers;

use App\Support\Portfolio;
use Illuminate\View\View;

class CaseStudyController extends Controller
{
    public function __invoke(Portfolio $portfolio, string $slug): View
    {
        $studies = $portfolio->caseStudies();
        $project = $studies->firstWhere('slug', $slug);

        abort_if($project === null, 404);

        return view('work.show', [
            'profile' => $portfolio->profile(),
            'project' => $project,
            'study' => $project['case_study'],
            'others' => $studies->where('slug', '!==', $slug)->values(),
        ]);
    }
}
