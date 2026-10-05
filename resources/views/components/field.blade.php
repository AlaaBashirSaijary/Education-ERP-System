@props(['name', 'label', 'type' => 'text', 'icon' => null, 'dir' => null, 'autocomplete' => null, 'required' => true, 'hint' => null, 'value' => null, 'placeholder' => null])
@php $isPassword = $type === 'password'; @endphp
<div x-data="{ show: false }" {{ $attributes->only('class') }}>
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    <div class="relative">
        @if ($icon)<span class="pointer-events-none absolute inset-y-0 start-3.5 grid place-items-center text-slate-400"><x-icon :name="$icon" class="h-4 w-4" /></span>@endif
        <input id="{{ $name }}" name="{{ $name }}" @if ($isPassword) :type="show ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
               value="{{ $isPassword ? '' : old($name, $value) }}" @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if ($dir) dir="{{ $dir }}" @endif @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
               {{ $attributes->except('class') }}
               class="input !py-3 {{ $icon ? 'ps-10' : '' }} {{ $isPassword ? 'pe-11' : '' }} @error($name) !ring-rose-500 @enderror">
        @if ($isPassword)
            <button type="button" @click="show = !show" class="absolute inset-y-0 end-2 grid w-9 place-items-center rounded-lg text-slate-400 hover:text-slate-700" :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'" tabindex="-1">
                <span x-show="!show"><x-icon name="eye" class="h-4 w-4" /></span><span x-show="show" x-cloak><x-icon name="eye-off" class="h-4 w-4" /></span>
            </button>
        @endif
    </div>
    @if ($hint)<p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p class="err">{{ $message }}</p>@enderror
</div>
