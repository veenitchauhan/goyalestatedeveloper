@foreach($sections as $section)
    @if($section['nav'])
        @php
            $destination = match ($section['id']) {
                'about', 'business', 'capabilities' => route('corporate.'.$section['id'].'.index'),
                'careers' => route('careers.index'),
                'insights' => route('knowledge.article.index'),
                'contact' => route('contact'),
                'projects' => route('projects.index'),
                default => route('home').'#'.$section['id'],
            };
            $destinationPath = trim(parse_url($destination, PHP_URL_PATH) ?? '', '/');
            $active = ! str_contains($destination, '#') && $destinationPath !== '' && (request()->is($destinationPath) || request()->is($destinationPath.'/*'));
        @endphp
        <a href="{{ $destination }}" @if($active) aria-current="page" @endif>{{ $section['nav'] }}</a>
    @endif
@endforeach
