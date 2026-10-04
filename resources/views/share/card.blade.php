{{-- A 1200x630 share image, turned into a PNG by `php artisan portfolio:share-images`. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @font-face { font-family: 'Display'; src: url('file://{{ $fonts }}/bricolage-grotesque/files/bricolage-grotesque-latin-opsz-normal.woff2'); font-weight: 200 800; }
        @font-face { font-family: 'Body'; src: url('file://{{ $fonts }}/hanken-grotesk/files/hanken-grotesk-latin-wght-normal.woff2'); font-weight: 100 900; }
        @font-face { font-family: 'Mono'; src: url('file://{{ $fonts }}/jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2'); font-weight: 100 800; }

        * { box-sizing: border-box; margin: 0; }

        body {
            width: 1200px;
            height: 630px;
            padding: 84px 80px 56px;
            display: flex;
            flex-direction: column;
            background: #15140F radial-gradient(circle, rgba(242, 238, 228, .09) 1.2px, transparent 1.3px) 0 0 / 26px 26px;
            color: #F2EEE4;
            font-family: 'Body', sans-serif;
        }

        .eyebrow { font-family: 'Mono', monospace; font-size: 24px; letter-spacing: .14em; text-transform: uppercase; color: #EE6044; }
        h1 { font-family: 'Display', sans-serif; font-weight: 700; letter-spacing: -.02em; line-height: 1.02; margin-top: 22px; max-width: 900px; }
        .rule { width: 430px; height: 12px; margin-top: 30px; background: #E14B33; border-radius: 6px; }
        .sub { font-size: 34px; line-height: 1.3; color: #C6C0B2; margin-top: 28px; max-width: 940px; }
        .mark { position: absolute; top: 118px; right: 96px; width: 50px; height: 50px; background: #E14B33; transform: rotate(45deg); border-radius: 4px; }
        footer { margin-top: auto; padding-top: 26px; border-top: 1px solid rgba(242, 238, 228, .2); display: flex; justify-content: space-between; font-family: 'Mono', monospace; font-size: 24px; }
        footer span:last-child { color: #8C867A; }
    </style>
</head>
<body>
    <div class="mark"></div>
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1 style="font-size: {{ mb_strlen($title) <= 14 ? 124 : (mb_strlen($title) <= 26 ? 96 : 72) }}px">{{ $title }}</h1>
    <div class="rule"></div>
    <p class="sub">{{ $subtitle }}</p>
    <footer><span>{{ $profile['website'] }}</span><span>{{ $profile['location'] }}</span></footer>
</body>
</html>
