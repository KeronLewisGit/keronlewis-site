<article class="blog-card">
    <p class="blog-meta"><span>{{ $post['category'] }}</span><span>{{ $post['minutes'] }} min read</span></p>
    <h3><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
    <p>{{ $post['description'] }}</p>
</article>
