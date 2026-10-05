@extends('layouts.admin')

@section('title', "Edit {$testimonial->sent_to}'s testimonial")

@section('content')
    <p class="back"><a href="{{ route('admin.testimonials') }}"><x-icon name="chevron-left" /> All testimonials</a></p>

    <form class="panel msg" method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}">
        @csrf
        @method('PUT')
        <h1 class="edit-title">Edit testimonial</h1>

        <div class="a-field">
            <label for="sent_to">Client's name</label>
            <input id="sent_to" name="sent_to" type="text" value="{{ old('sent_to', $testimonial->sent_to) }}" maxlength="120" required>
            <p class="a-hint">Shown beside the testimonial on the site.</p>
            @error('sent_to')<p class="a-error">{{ $message }}</p>@enderror
        </div>

        <div class="a-field">
            <label for="project">Project or company</label>
            <input id="project" name="project" type="text" value="{{ old('project', $testimonial->projectName()) }}" maxlength="160" list="project-names" autocomplete="off">
            <datalist id="project-names">
                @foreach ($projects as $name)
                    <option value="{{ $name }}"></option>
                @endforeach
            </datalist>
            <p class="a-hint">Shown after the name. If it matches a project in your portfolio, the testimonial also appears on that project's case study.</p>
            @error('project')<p class="a-error">{{ $message }}</p>@enderror
        </div>

        @if ($testimonial->submitted_at)
            <div class="a-field">
                <label for="quote">Testimonial</label>
                <textarea id="quote" name="quote" rows="7" maxlength="600" required>{{ old('quote', $testimonial->quote) }}</textarea>
                <p class="a-hint">These are your client's words, so keep changes to spelling and punctuation.</p>
                @error('quote')<p class="a-error">{{ $message }}</p>@enderror
            </div>

            <div class="a-field">
                <label for="highlights">Phrases to highlight</label>
                <textarea id="highlights" name="highlights" rows="3" placeholder="from 100 to over 300 students">{{ old('highlights', implode("\n", $testimonial->highlights ?? [])) }}</textarea>
                <p class="a-hint">One per line, copied word for word from the testimonial. Each is marked in the text, and the first is also shown large above it. Give every testimonial one so the cards on the site match.</p>
                @error('highlights')<p class="a-error">{{ $message }}</p>@enderror
            </div>
        @else
            <p class="a-hint t-intro">The client hasn't written anything yet. You can edit the wording once they have.</p>
        @endif

        <div class="t-actions">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="{{ route('admin.testimonials') }}">Cancel</a>
        </div>
    </form>
@endsection
