@props(['name' => '?', 'size' => 'h-9 w-9 text-sm'])
@php
    $words = preg_split('/\s+/u', trim($name)) ?: [$name];
    $initials = mb_substr($words[0] ?? '?', 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : '');
    $tone = crc32($name) % 8;
@endphp
<span {{ $attributes->merge(['class' => "tone-$tone inline-flex shrink-0 items-center justify-center rounded-full font-bold $size"]) }} aria-hidden="true">{{ $initials }}</span>
