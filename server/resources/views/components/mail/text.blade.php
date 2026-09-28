{{-- Body copy. One style for every paragraph in every message. --}}
@props(['space' => 0, 'muted' => false])
<p style="margin: {{ $space }}px 0 0 0; color: {{ $muted ? '#8b919c' : '#4b5563' }}; font-size: {{ $muted ? '13' : '16' }}px; line-height: 1.65;">{{ $slot }}</p>
