@foreach($sections as $section)
    @if($section['nav'] && !in_array($section['id'],['business','capabilities','insights']))
        @php
            $destination = match ($section['id']) {
                'about' => route('corporate.'.$section['id'].'.index'),
                'careers' => route('careers.index'),
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

<a href="{{ route('knowledge.blog.index') }}" @if(request()->routeIs('knowledge.blog.*')) aria-current="page" @endif>Blog</a>
