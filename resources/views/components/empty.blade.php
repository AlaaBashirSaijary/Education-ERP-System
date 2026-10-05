@props(['icon' => 'students', 'title'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 px-4 py-12 text-center']) }}>
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400"><x-icon :name="$icon" class="h-7 w-7" /></span>
    <div class="font-semibold text-slate-700">{{ $title }}</div>
    @if (trim($slot))<div class="max-w-sm text-sm text-slate-500">{{ $slot }}</div>@endif
</div>
