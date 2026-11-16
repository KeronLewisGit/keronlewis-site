{{-- /llms.txt (llmstxt.org): plain facts for AI assistants, built from config/portfolio.php so it never drifts from the pages. --}}
# {!! $profile['name'] !!}

> {!! $profile['name'] !!} is a freelance full-stack web developer in {!! $profile['location'] !!}. He builds business websites, WordPress and WooCommerce stores, custom web apps and API integrations for businesses in Trinidad and Tobago and the Caribbean, and has done so since 2016. He also runs the web studio Code Canvas Consultants Ltd (https://codecanvastt.com).

## Key facts

- Name: {!! $profile['name'] !!}
- Role: {!! $profile['title'] !!} (web developer)
- Based in: {!! $profile['location'] !!}
- Serves: Trinidad and Tobago, the wider Caribbean, and remote clients
- Building for the web since: 2016
- Prices: websites {!! Str::lcfirst($profile['price_range']) !!}. {!! $profile['offer']['text'] !!}.
- Availability: {!! $profile['availability'] !!}. {!! $profile['remote'] !!}.
- Email: {!! $profile['email'] !!}
- Phone and WhatsApp: {!! $profile['phone'] !!}
- Website: {{ url('/') }}
@foreach ($profile['links'] as $link)
- {!! $link['label'] !!}: {!! $link['url'] !!}
@endforeach

## Summary

{!! $profile['summary'] !!}

## Services

@foreach ($services as $service)
- [{!! $service['page']['heading'] !!}]({{ $service['url'] }}): {!! $service['text'] !!}
@endforeach

## Live sites

@foreach ($projects->reject(fn ($project) => str_contains($project['url'], 'hostingersite.com')) as $project)
- [{!! $project['name'] !!}]({!! $project['url'] !!}) ({!! $project['category'] !!}): {!! $project['summary'] !!}
@endforeach

## Concept builds

Full working previews built for prospective clients, on preview addresses. They are not launched client sites.

@foreach ($projects->filter(fn ($project) => str_contains($project['url'], 'hostingersite.com')) as $project)
- [{!! $project['name'] !!}]({!! $project['url'] !!}) ({!! $project['category'] !!})
@endforeach

## Experience

@foreach ($experience as $role)
- {!! $role['role'] !!}, {!! $role['org'] !!} ({!! $role['period'] !!})
@endforeach

## Skills

@foreach ($skills as $group => $items)
- {!! $group !!}: {!! implode(', ', $items) !!}
@endforeach

## Education

@foreach (config('portfolio.education') as $item)
- {!! $item['award'] !!}, {!! $item['school'] !!} ({!! $item['period'] !!}, {!! $item['status'] !!})
@endforeach

## Pages

- [Home]({{ route('home') }}): services, selected work, experience and contact
- [Résumé]({{ route('resume') }}): full CV, also as [PDF]({{ route('resume.pdf') }}) and [JSON Resume]({{ route('resume.json') }})
@if ($bookingOpen)
- [Book a call]({{ route('booking.show') }})
@endif
- [Blog]({{ route('blog.index') }}): articles on Laravel, AI and web development in the Caribbean
@foreach ($posts->take(20) as $post)
  - [{!! $post['title'] !!}]({{ $post['url'] }})
@endforeach
