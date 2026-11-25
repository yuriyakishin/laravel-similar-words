@php
    $t = fn (string $key, array $replace = []) => __('similar-words::messages.'.$key, $replace, $locale);
    $relations = __('similar-words::messages.relations', [], $locale);
    $verdict = fn (float $score): string => match (true) {
        $score >= 0.8 => 'same',
        $score >= 0.6 => 'close',
        $score >= 0.45 => 'related',
        $score >= 0.3 => 'weak',
        default => 'unrelated',
    };
@endphp

<div class="wt sw" lang="{{ $locale }}" x-data="{
        step: 0,
        timer: null,
        start() { this.step = 0; clearInterval(this.timer); this.timer = setInterval(() => this.step = Math.min(this.step + 1, 2), 2500) },
        {{-- The word goes into the field at once, not when the search comes back --}}
        pick(phrase) { this.$wire.phrase = phrase; this.start(); this.$wire.searchFor(phrase) },
    }" @sw-pick="pick($event.detail)">
    <form class="wt-tool" wire:submit="search" @submit="start()" novalidate>
        <div class="wt-row">
            <div @class(['wt-field', 'wt-field-err' => $errors->has('phrase')])>
                <input id="sw-phrase" type="text" autocomplete="off" placeholder=" " maxlength="{{ config('similar-words.max_length') }}"
                       wire:model="phrase">
                <label for="sw-phrase">{{ $t('form.label') }}</label>
            </div>
            <button type="submit" class="wt-go" wire:loading.attr="disabled" wire:target="search,searchFor">
                <span wire:loading.remove wire:target="search,searchFor">{{ $t('form.button') }}</span>
                <span wire:loading wire:target="search,searchFor" class="wt-spin" aria-hidden="true"></span>
            </button>
        </div>
        <div class="sw-examples">
            <span>{{ $t('form.examples') }}</span>
            @foreach ($t('examples') as $example)
                <button type="button" @class(['sw-chip', 'sw-on' => $report && $report['phrase'] === $example])
                        @click="pick(@js($example))">{{ $example }}</button>
            @endforeach
        </div>
        @error('phrase')<p class="wt-error">{{ $message }}</p>@enderror
        @if ($error)
            <p class="wt-error" role="alert">{{ $error }}</p>
        @endif
    </form>

    {{-- Lines light up on a timer while one request runs; the server does not report its steps --}}
    <div class="wt-log" wire:loading.block wire:target="search,searchFor" aria-hidden="true">
        @foreach (['candidates', 'embeddings', 'map'] as $i => $line)
            <p :class="{ 'wt-run': step === {{ $i }}, 'wt-done': step > {{ $i }} }">{{ $t('loading.'.$line) }}</p>
        @endforeach
    </div>

    @if ($report)
        {{-- The key replaces the block for a new report, which scrolls the tool up to its form; wire:ignore keeps the drawn map when only the comparison
             below changes. Drawing is in resources/js/similar-words.js --}}
        <div wire:key="sw-report-{{ $reportNumber }}" wire:loading.remove wire:target="search,searchFor" aria-live="polite"
             x-init="$root.scrollIntoView({ behavior: 'smooth', block: 'start' })">
            <div wire:ignore x-data="similarWords(@js($report))">
                {{-- Hides or shows a relation on the map and in the ranking at once --}}
                <div class="sw-filter" role="group" aria-label="{{ $t('show') }}">
                    <span>{{ $t('show') }}</span>
                    @foreach ($relations as $relation => $label)
                        <button type="button" class="sw-toggle sw-{{ $relation }}" data-label="{{ $label }}" :class="{ 'sw-off': hidden.includes('{{ $relation }}') }"
                                :aria-pressed="!hidden.includes('{{ $relation }}')" @click="toggle('{{ $relation }}')">
                            <i></i>{{ $label }} <b>{{ count(array_filter($report['items'], fn (array $item): bool => $item['relation'] === $relation)) }}</b>
                        </button>
                    @endforeach
                </div>

                <div class="sw-res">
                    <div class="sw-card">
                        <div class="sw-ch">
                            <div><h3>{{ $t('map.title') }}</h3><small x-text="view === 'radar' ? @js($t('map.radar_sub')) : @js($t('map.plane_sub'))">{{ $t('map.radar_sub') }}</small></div>
                            <div class="sw-seg">
                                <button type="button" :class="{ 'sw-on': view === 'radar' }" @click="view = 'radar'">{{ $t('map.radar') }}</button>
                                <button type="button" :class="{ 'sw-on': view === 'plane' }" @click="view = 'plane'">{{ $t('map.plane') }}</button>
                            </div>
                        </div>
                        <div class="sw-map" @mouseleave="highlight = null">
                            <svg x-ref="svg" viewBox="0 0 640 560" role="img" aria-label="{{ $t('map.label') }}"
                                 @mouseover="pointAt($event)" @click="pinAt($event)" @dblclick="open($event)"></svg>
                            <div class="sw-tip" x-ref="tip" data-green="{{ $t('print.green') }}" data-red="{{ $t('print.red') }}"></div>
                        </div>
                        <div class="sw-foot">{{ $t('map.click') }}</div>
                    </div>
    
                    <div class="sw-card">
                        <div class="sw-ch"><div><h3>{{ $t('rank.title') }}</h3><small>{{ $t('rank.sub') }}</small></div></div>
                        <ol class="sw-rank" @mouseleave="highlight = null">
                            @foreach ($report['items'] as $i => $item)
                                <li class="sw-{{ $item['relation'] }}" x-show="!hidden.includes('{{ $item['relation'] }}')"
                                    :class="{ 'sw-hl': highlight === {{ $i }}, 'sw-pin': pinned === {{ $i }} }" :aria-current="pinned === {{ $i }}" @mouseenter="highlight = {{ $i }}"
                                    @click="pin({{ $i }})" @dblclick="pick(@js($item['text']))" title="{{ $relations[$item['relation']] }}">
                                    <span class="sw-n">{{ sprintf('%02d', $i + 1) }}</span>
                                    <span class="sw-w"><i></i><span>{{ $item['text'] }}</span></span>
                                    <span class="sw-s">{{ number_format(max(0, $item['score']), 2) }}</span>
                                    <span class="sw-bar" style="--v: {{ max(0, $item['score']) * 100 }}%"><i></i></span>
                                </li>
                            @endforeach
                        </ol>
                        <div class="sw-foot">{{ $t('rank.click') }}</div>
                    </div>
                </div>
    
                <div class="sw-card sw-print">
                    <div class="sw-ch">
                        <div><h3>{{ $t('print.title') }}</h3><small>{{ $t('print.sub', ['count' => count($report['query']['print']), 'total' => $report['dimensions']]) }}</small></div>
                        <span class="sw-scale" aria-hidden="true">{{ $t('print.against') }}<i></i>{{ $t('print.agree') }}</span>
                    </div>
                    <div class="sw-fp">
                        <div class="sw-fp-row">
                            <div class="sw-fp-lbl"><b>{{ $report['phrase'] }}</b><small>{{ $t('print.yours') }}</small></div>
                            <div class="sw-cells" x-html="cells(shown().print)"></div>
                            <div class="sw-fp-lbl"><b x-text="shown().text"></b><small x-html="@js($t('print.score', ['score' => '%s'])).replace('%s', shown().score.toFixed(2)) + ' · ' + counts(shown().print, @js($t('print.green')), @js($t('print.red')))"></small></div>
                        </div>
                        <p class="sw-note">{{ $t('print.hint') }} {{ $t('model', ['model' => $report['model'], 'dimensions' => $report['dimensions']]) }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="sw-card sw-compare">
        <div class="sw-ch"><div><h3>{{ $t('compare.title') }}</h3><small>{{ $t('compare.sub') }}</small></div></div>
        <form class="sw-cmp" wire:submit="compare" novalidate>
            <input type="text" wire:model="first" aria-label="{{ $t('compare.first') }}" placeholder="{{ $t('compare.first') }}" maxlength="{{ config('similar-words.max_length') }}">
            <span class="sw-vs" aria-hidden="true">↔</span>
            <input type="text" wire:model="second" aria-label="{{ $t('compare.second') }}" placeholder="{{ $t('compare.second') }}" maxlength="{{ config('similar-words.max_length') }}">
            <button type="submit" class="wt-go" wire:loading.attr="disabled" wire:target="compare">
                <span wire:loading.remove wire:target="compare">{{ $t('compare.button') }}</span>
                <span wire:loading wire:target="compare" class="wt-spin" aria-hidden="true"></span>
            </button>
        </form>
        @if ($errors->has('first') || $errors->has('second'))<p class="wt-error">{{ $errors->first('first') ?: $errors->first('second') }}</p>@endif
        @if ($compareError)
            <p class="wt-error" role="alert">{{ $compareError }}</p>
        @endif
        @if ($similarity !== null)
            <div class="sw-gauge" aria-live="polite">
                <p><b>{{ number_format($similarity, 2) }}</b><span>{{ $t('compare.verdicts.'.$verdict($similarity)) }}</span></p>
                <div class="sw-gscale" style="--v: {{ max(0, min(1, $similarity)) * 100 }}%"><i></i></div>
                <div class="sw-marks"><span>0 · {{ $t('compare.none') }}</span><span>0.5 · {{ $t('compare.related') }}</span><span>1 · {{ $t('compare.same') }}</span></div>
            </div>
        @endif
    </div>
</div>
