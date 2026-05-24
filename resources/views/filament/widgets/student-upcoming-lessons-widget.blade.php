<x-filament-widgets::widget>
    <div class="flex h-full flex-col overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white shadow-sm transition-all dark:border-gray-800 dark:bg-gray-900/50">
        <!-- Header -->
        <div class="relative flex items-center justify-between overflow-hidden px-8 py-6">
            <div class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 rounded-full bg-violet-400 opacity-[0.05] blur-2xl"></div>
            
            <div class="relative flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-black tracking-tight text-gray-950 dark:text-white">
                    Ближайшие уроки
                </h3>
            </div>
            
            @if($upcomingLessons->isNotEmpty())
                 <span class="rounded-full bg-violet-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                    След. {{ $upcomingLessons->first()->start_time->isoFormat('D MMM') }}
                 </span>
            @endif
        </div>

        <!-- Body -->
        <div class="flex-1 px-4 pb-8 pt-2 sm:px-8">
            @if ($upcomingLessons->isEmpty())
                <div class="flex h-full min-h-[200px] flex-col items-center justify-center text-center">
                    <div class="relative mb-6">
                        <div class="absolute inset-0 animate-ping rounded-full bg-violet-100 opacity-20"></div>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-violet-50 text-violet-400">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Пока пусто</h4>
                    <p class="mt-2 text-sm font-medium text-gray-500 max-w-[180px] leading-relaxed">У вас нет запланированных уроков на ближайшее время</p>
                    <a href="/tutors" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-violet-600 px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-violet-200 transition hover:bg-violet-700 active:scale-95 dark:shadow-none">
                        Найти репетитора
                    </a>
                </div>
            @else
                <div class="grid gap-3">
                    @foreach ($upcomingLessons as $lesson)
                        @php
                            $isStartingSoon = $lesson->start_time->isPast() || $lesson->start_time->diffInMinutes(now(), false) >= -15;
                            $tutor = $lesson->tutor;
                            $profile = $tutor->tutorProfile;
                        @endphp
                        
                        <div class="group relative flex items-center justify-between rounded-3xl border border-gray-100 bg-white p-4 transition hover:border-violet-100 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-violet-900/30 {{ $isStartingSoon ? 'ring-2 ring-violet-500/20' : '' }}">
                            
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                     <x-filament::avatar
                                        :src="$profile?->photo ? Storage::url($profile->photo) : null"
                                        :alt="$tutor->name"
                                        size="lg"
                                        class="!rounded-2xl"
                                    />
                                    @if($isStartingSoon)
                                        <span class="absolute -bottom-1 -right-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-white dark:bg-gray-800">
                                            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                                        </span>
                                    @endif
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-black text-gray-950 dark:text-white transition group-hover:text-violet-600 dark:group-hover:text-violet-400">{{ $tutor->name }}</h4>
                                    <div class="mt-1 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-gray-500">
                                            <svg class="h-3.5 w-3.5 text-violet-500 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $lesson->start_time->isoFormat('D MMM, HH:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ml-4 shrink-0">
                                @if($isStartingSoon && $lesson->meeting_link)
                                    <a href="{{ $lesson->meeting_link }}" target="_blank" class="flex h-10 items-center gap-2 rounded-xl bg-violet-600 px-4 text-xs font-black text-white transition hover:bg-violet-700 shadow-lg shadow-violet-200 dark:shadow-none active:scale-95">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Войти
                                    </a>
                                @else
                                    <a href="{{ url('/admin/lessons/' . $lesson->id) }}" class="flex h-10 items-center rounded-xl border border-gray-100 bg-gray-50/50 px-4 text-xs font-bold text-gray-600 transition hover:bg-white hover:border-violet-100 hover:text-violet-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white group-hover:shadow-sm">
                                        Детали
                                    </a>
                                @endif
                            </div>
                            
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
