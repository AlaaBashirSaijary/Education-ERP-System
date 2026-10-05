<div class="mx-auto max-w-2xl space-y-4">
    <x-flash />
    <form wire:submit="send" class="card space-y-4">
        <p class="text-sm text-slate-500">{{ __('Send a WhatsApp/SMS message to parents (delivery channel is set in the server configuration).') }}</p>
        <div><label class="label">{{ __('Send to') }}</label>
            <select wire:model="audience" class="input"><option value="all">{{ __('All parents') }}</option>
                @foreach ($classes as $c)<option value="{{ $c->id }}">{{ __('Parents of') }} {{ $c->name }} {{ $c->section }}</option>@endforeach</select>
            @error('audience')<p class="err">{{ $message }}</p>@enderror</div>
        <div><label class="label">{{ __('Message') }}</label><textarea wire:model="message" rows="5" maxlength="500" class="input"></textarea>
            <div class="mt-1 flex justify-between text-xs text-slate-400"><span>@error('message')<span class="err">{{ $message }}</span>@enderror</span><span dir="ltr">{{ strlen($message) }}/500</span></div></div>
        <button class="btn-primary" wire:loading.attr="disabled" wire:confirm="{{ __('Send this message now?') }}">{{ __('Send') }}</button>
    </form>
</div>
