<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ $profile['name'] }}'s blog</title>
    <link>{{ route('blog.index') }}</link>
    <description>AI, Laravel and web development, from {{ $profile['location'] }}.</description>
    <language>en</language>
    <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
    @if ($updated)
      <lastBuildDate>{{ $updated->toRssString() }}</lastBuildDate>
    @endif
@foreach ($posts as $post)
    <item>
      <title>{{ $post['title'] }}</title>
      <link>{{ $post['url'] }}</link>
      <guid isPermaLink="true">{{ $post['url'] }}</guid>
      <pubDate>{{ $post['date']->toRssString() }}</pubDate>
      <category>{{ $post['category'] }}</category>
      <description>{{ $post['description'] }}</description>
    </item>
@endforeach
  </channel>
</rss>
