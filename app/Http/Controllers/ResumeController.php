<?php

namespace App\Http\Controllers;

use App\Support\Portfolio;
use Barryvdh\DomPDF\Facade\Pdf;

class ResumeController extends Controller
{
    public function show(Portfolio $portfolio)
    {
        return view('resume.show', $this->data($portfolio) + [
            'chart' => $portfolio->careerChart(),
            'skillIndex' => $portfolio->skillIndex(),
            'projects' => $portfolio->projects(),
            'highlights' => config('portfolio.highlights'),
        ]);
    }

    public function pdf(Portfolio $portfolio)
    {
        return Pdf::loadView('resume.pdf', $this->data($portfolio))
            ->setPaper('a4')
            ->download('Keron-Lewis-Resume.pdf');
    }

    public function json(Portfolio $portfolio)
    {
        return response()->json(
            $portfolio->toJsonResume(),
            options: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    public function vcard(Portfolio $portfolio)
    {
        return response($portfolio->toVCard(), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="keron-lewis.vcf"',
        ]);
    }

    private function data(Portfolio $portfolio): array
    {
        return [
            'profile' => $portfolio->profile(),
            'experience' => $portfolio->experience(),
            'skills' => $portfolio->skills(),
            'education' => config('portfolio.education'),
            'certifications' => config('portfolio.certifications'),
        ];
    }
}
