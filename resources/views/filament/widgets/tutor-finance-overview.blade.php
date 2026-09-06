<div class="tutor-finance">
    @foreach ($stats as $stat)
        <a href="{{ $stat['href'] }}" class="tutor-stat {{ $stat['accent'] ? 'tutor-stat--accent' : '' }}">
            <span class="tutor-stat-label">{{ $stat['label'] }}</span>
            <span>
                <span class="tutor-stat-value">{{ $stat['value'] }}</span>
                <span class="tutor-stat-hint">{{ $stat['hint'] }}</span>
            </span>
        </a>
    @endforeach
</div>
