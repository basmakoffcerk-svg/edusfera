<x-filament-widgets::widget>
    <div class="relative h-full overflow-hidden rounded-3xl border border-white/60 bg-white/90 shadow-[0_24px_90px_-45px_rgba(15,23,42,0.45)]">
        <div class="pointer-events-none absolute -left-12 -top-8 h-40 w-40 rounded-full bg-lime-200/30 blur-2xl"></div>
        <div class="relative flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <h3 class="flex items-center gap-2 text-base font-black tracking-[-0.01em] text-gray-950">
                <svg class="h-5 w-5 text-lime-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                Мои преподаватели
            </h3>
        </div>

        <div class="flex-1 p-6">
            @if ($tutors->isEmpty())
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-lime-100">
                        <svg class="h-6 w-6 text-lime-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-600">У вас пока нет репетиторов</p>
                    <a href="/tutors" class="mt-4 text-sm font-bold text-violet-600 transition hover:text-violet-500">Перейти в каталог →</a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($tutors as $tutor)
                        @php
                            $profile = $tutor->tutorProfile;
                        @endphp
                        
                        <div class="flex items-center justify-between rounded-2xl border border-gray-200/80 bg-white p-3 transition hover:-translate-y-0.5 hover:border-lime-300 hover:shadow-sm">
                            
                            <div class="flex items-center gap-3">
                                <x-filament::avatar
                                    :src="$profile?->photo ? Storage::url($profile->photo) : null"
                                    :alt="$tutor->name"
                                    size="md"
                                />
                                
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">{{ $tutor->name }}</h4>
                                    <p class="mt-0.5 line-clamp-1 text-xs font-medium text-gray-600">
                                        {{ $profile?->subjects ? implode(', ', $profile->subjects) : 'Преподаватель' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <x-filament::button tag="a" href="{{ url('/tutors/' . $tutor->id) }}" color="gray" size="sm" variant="outline" class="hidden !rounded-xl !font-bold sm:inline-flex">
                                    Профиль
                                </x-filament::button>
                                
                                <form action="{{ route('tutors.conversation', $tutor->id) }}" method="POST" class="inline">
                                    @csrf
                                    <x-filament::button type="submit" color="primary" size="sm" icon="heroicon-m-chat-bubble-left-ellipsis" class="!rounded-xl !font-bold">
                                        Написать
                                    </x-filament::button>
                                </form>
                            </div>
                            
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
