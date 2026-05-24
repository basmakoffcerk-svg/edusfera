<x-filament-widgets::widget>
    <section class="rounded-[28px] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-950 text-xl font-black text-white">
                    {{ mb_substr($name, 0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Инфопанель</p>
                    <h2 class="mt-1 text-3xl font-black tracking-[-0.05em] text-stone-950">Здравствуйте, {{ $name }}</h2>
                    <p class="mt-1 text-sm text-stone-600">Здесь только то, что влияет на заявки, уроки и деньги.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="/admin/lesson-requests" class="inline-flex min-h-11 items-center rounded-xl bg-stone-950 px-4 text-sm font-black text-white transition hover:bg-stone-800">
                    Новые заявки
                </a>
                <a href="/admin/lessons" class="inline-flex min-h-11 items-center rounded-xl border border-stone-200 px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400 hover:bg-lime-50">
                    Расписание
                </a>
                <a href="/admin/messages" class="inline-flex min-h-11 items-center rounded-xl border border-stone-200 px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400 hover:bg-lime-50">
                    Сообщения
                </a>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
