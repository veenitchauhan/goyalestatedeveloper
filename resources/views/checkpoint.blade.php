<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ config('app.name') }} | Development checkpoint</title>
    <link rel="stylesheet" href="/assets/checkpoint.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header><span class="brand">{{ config('app.name') }}</span><span class="environment">LOCAL DEVELOPMENT</span></header>
<main id="main">
    <section class="intro">
        <p class="eyebrow">DEVELOPMENT CHECKPOINT / 01</p>
        <h1>A considered start.<br>A solid foundation.</h1>
        <p class="description">The Laravel foundation for your corporate digital platform. Review each module before the next one begins.</p>
        <a class="button" href="{{ route('health') }}">Check local connection <span aria-hidden="true">↗</span></a>
        <p><a href="{{ route('login') }}">Open administration →</a></p><p class="note">Development checkpoint only. The homepage visual direction is approved. Full modules are counted separately; the PDF audit identifies the remaining work.</p>
    </section>
    <section class="panel" aria-labelledby="progress-title">
        <p class="eyebrow" id="progress-title">DELIVERY PROGRESS</p>
        <div class="stats"><div><strong>{{ count(config('development.modules')) }}</strong><span>Total modules</span></div><div><strong>{{ config('development.completed') }}</strong><span>Completed</span></div><div><strong>{{ count(config('development.modules')) - config('development.completed') }}</strong><span>Remaining</span></div></div>
        <p class="status">{{ config('development.status') }}</p>
        <p>Development pauses after every module for your testing and approval.</p>
    </section>
    <section class="roadmap" aria-labelledby="roadmap-title">
        <div class="section-heading"><h2 id="roadmap-title">The path ahead</h2><span>One module at a time</span></div>
        <ol>@foreach(config('development.modules') as $index => $module)<li><span class="number">{{ sprintf('%02d', $index + 1) }}</span><span>{{ $module }}</span><small>{{ in_array($index + 1, config('development.completed_ids')) ? 'Complete' : (in_array($index + 1, config('development.partial_ids', [])) ? 'Partial' : ($index + 1 === config('development.current_module') ? 'In progress' : 'Upcoming')) }}</small></li>@endforeach</ol>
    </section>
</main>
<footer><span>{{ config('app.name') }}</span><span>{{ config('development.host') }}</span></footer>
</body>
</html>
