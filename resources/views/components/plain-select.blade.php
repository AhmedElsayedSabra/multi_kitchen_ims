@props([
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'error' => null,
    'required' => false,
    'searchable' => false,
])

@php
    $selectOptions = $options instanceof \Illuminate\Support\Collection ? $options->all() : (array) $options;
    $placeholderText = $placeholder ?: ($label ? '-- Select ' . $label . ' --' : '-- Select --');
@endphp

<div class="w-full">
    @if ($label)
        <label class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }}
    @endif

    <select
        {{ $attributes->merge([
            'class' => 'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20',
            'style' => 'background-color: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.5rem 0.75rem; width: 100%; font-size: 0.875rem;',
        ]) }}
        @required($required)
    >
        <option value="" style="background-color: #ffffff; color: #0f172a;">{{ $placeholderText }}</option>
        @foreach ($selectOptions as $value => $option)
            @if ((string) $value !== '')
                @php
                    $optionValue = is_array($option) || is_object($option)
                        ? data_get($option, 'value', data_get($option, 'id', $value))
                        : $value;
                    $optionLabel = is_array($option) || is_object($option)
                        ? data_get($option, 'label', data_get($option, 'name', $optionValue))
                        : $option;
                    $optionDisabled = is_array($option) || is_object($option)
                        ? data_get($option, 'disabled', false)
                        : false;
                @endphp
                <option
                    value="{{ $optionValue }}"
                    @disabled($optionDisabled)
                    style="background-color: #ffffff; color: #0f172a;"
                >{{ $optionLabel }}</option>
            @endif
        @endforeach
    </select>

    @if ($label)
        </label>
    @endif

    @if ($error)
        <p class="mt-1 text-xs text-rose-600">{{ $error }}</p>
    @endif
</div>
