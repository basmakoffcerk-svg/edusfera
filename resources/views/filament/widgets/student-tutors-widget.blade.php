<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-academic-cap">
        <x-slot name="heading">
            Мои преподаватели
        </x-slot>

        <x-slot name="description">
            Репетиторы, с которыми вы занимаетесь на платформе
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button href="/tutors" tag="a" color="gray" size="xs" icon="heroicon-o-plus">
                Добавить
            </x-filament::button>
        </x-slot>

        @if ($tutors->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 p-6 text-center space-y-3">
                <p class="text-sm font-bold text-gray-900 dark:text-white">У вас пока нет репетиторов</p>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">Перейдите в каталог, чтобы подобрать проверенного преподавателя.</p>
                <x-filament::button href="/tutors" tag="a" color="primary" size="sm" icon="heroicon-o-magnifying-glass">
                    Перейти в каталог
                </x-filament::button>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($tutors as $tutor)
                    @php
                        $profile = $tutor->tutorProfile;
                    @endphp

                    <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold">
                                {{ mb_substr($tutor->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tutor->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ !empty($profile?->subjects) ? implode(', ', (array)$profile->subjects) : 'Репетитор' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <x-filament::button href="/admin/messages" tag="a" color="gray" size="xs">
                                Сообщение
                            </x-filament::button>
                            <x-filament::button href="/admin/lessons" tag="a" color="primary" size="xs">
                                Занятия
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
