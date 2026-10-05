@props(['class' => 'h-9 w-9'])
{{-- School emblem: an eight-pointed star made of two squares, with a saffron centre. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 48 48" fill="none" aria-hidden="true">
    <rect x="9" y="9" width="30" height="30" rx="3" fill="currentColor"/>
    <rect x="9" y="9" width="30" height="30" rx="3" transform="rotate(45 24 24)" fill="currentColor" opacity=".55"/>
    <circle cx="24" cy="24" r="6.5" fill="rgb(var(--saffron))"/>
</svg>
