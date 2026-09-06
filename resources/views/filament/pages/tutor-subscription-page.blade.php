<x-filament-panels::page>
    <div class="space-y-6">
        @if($subscription)
            {{-- Current Plan Overview Banner --}}
            <div class="p-6 rounded-2xl border bg-gradient-to-r from-gray-900 to-gray-800 text-white shadow-xl relative overflow-hidden border-gray-700">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-black uppercase tracking-widest px-3 py-1 rounded-full bg-lime-400 text-black">
                                Тариф {{ $subscription->plan->title() }}
                            </span>
                            @if($subscription->is_founder)
                                <span class="text-xs font-black uppercase tracking-widest px-3 py-1 rounded-full bg-amber-400 text-black flex items-center gap-1">
                                    👑 Основатель платформы
                                </span>
                            @endif
                            <span class="text-xs font-bold px-3 py-1 rounded-full bg-white/10 text-gray-200">
                                {{ $subscription->status->title() }}
                            </span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight">
                            @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL)
                                Бесплатный пробный период: осталось <span class="text-lime-400">{{ $subscription->daysRemaining() }} дней</span>
                            @else
                                Подписка активна до <span class="text-lime-400">{{ $subscription->current_period_ends_at?->format('d.m.Y') }}</span>
                            @endif
                        </h2>

                        <p class="text-sm text-gray-300 max-w-xl">
                            Вам доступен полный функционал тарифа {{ $subscription->plan->title() }}. 
                            @if($subscription->is_founder)
                                Для вас навсегда зафиксирована цена раннего доступа.
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <x-filament::button wire:click="createNewInvoice" color="success" size="lg" icon="heroicon-o-document-plus">
                            Сформировать счёт в ЕРИП
                        </x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Plans Selector --}}
            <x-filament::section icon="heroicon-o-squares-2x2">
                <x-slot name="heading">
                    Выбор и смена тарифного плана
                </x-slot>
                <x-slot name="description">
                    Первый месяц бесплатно на любом тарифе. Вы можете перейти на любой тариф в один клик.
                </x-slot>

                <div class="grid md:grid-cols-3 gap-6 mt-4">
                    {{-- Basic --}}
                    <div class="p-6 rounded-2xl border transition-all flex flex-col justify-between {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC ? 'border-primary-500 bg-primary-500/5 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-800' }}">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold">Basic</h3>
                                @if($subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC)
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-primary-500 text-white">Текущий</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-3xl font-black">20 BYN</span>
                                <span class="text-xs text-gray-500 font-medium">/ месяц</span>
                            </div>
                            <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                                <li>✓ Базовый профиль в каталоге</li>
                                <li>✓ Просмотр заявок от родителей</li>
                                <li>✓ Базовый чат с учениками</li>
                                <li>✓ Email-уведомления</li>
                            </ul>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <x-filament::button 
                                wire:click="changePlan('basic')" 
                                color="{{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC ? 'gray' : 'primary' }}" 
                                size="sm" 
                                class="w-full"
                                :disabled="$subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC"
                            >
                                {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC ? 'Выбран' : 'Перейти на Basic' }}
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Pro --}}
                    <div class="p-6 rounded-2xl border transition-all flex flex-col justify-between relative {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO ? 'border-success-500 bg-success-500/5 ring-2 ring-success-500' : 'border-gray-200 dark:border-gray-800' }}">
                        <div class="absolute -top-3 right-4 px-2.5 py-0.5 rounded-full bg-lime-400 text-black font-black text-[10px] uppercase">
                            Выбор большинства
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold">Pro</h3>
                                @if($subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO)
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-success-500 text-white">Текущий</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-3xl font-black text-success-600 dark:text-success-400">40 BYN</span>
                                <span class="text-xs text-gray-500 font-medium">/ месяц</span>
                            </div>
                            <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                                <li>✓ <strong>Расширенный профиль</strong> (отзывы, дипломы)</li>
                                <li>✓ <strong>До 10 откликов в месяц</strong> на заявки</li>
                                <li>✓ <strong>Онлайн-календарь и автонапоминания</strong></li>
                                <li>✓ Чат с файлами и голосовыми</li>
                                <li>✓ <strong>Аналитика и отчёты для НПД</strong></li>
                            </ul>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <x-filament::button 
                                wire:click="changePlan('pro')" 
                                color="{{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO ? 'gray' : 'success' }}" 
                                size="sm" 
                                class="w-full"
                                :disabled="$subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO"
                            >
                                {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO ? 'Выбран' : 'Перейти на Pro' }}
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Premium --}}
                    <div class="p-6 rounded-2xl border transition-all flex flex-col justify-between {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM ? 'border-amber-500 bg-amber-500/5 ring-2 ring-amber-500' : 'border-gray-200 dark:border-gray-800' }}">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold">Premium</h3>
                                @if($subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM)
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500 text-white">Текущий</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-3xl font-black">60 BYN</span>
                                <span class="text-xs text-gray-500 font-medium">/ месяц</span>
                            </div>
                            <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                                <li>✓ <strong>Топ-5 в каталоге</strong> + видео-визитка</li>
                                <li>✓ <strong>Безлимитные отклики</strong> + автоподбор</li>
                                <li>✓ <strong>Синхронизация с Google Calendar</strong></li>
                                <li>✓ <strong>Видео-комната до 45 мин</strong></li>
                                <li>✓ Приоритетная поддержка 2ч + SMS</li>
                            </ul>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <x-filament::button 
                                wire:click="changePlan('premium')" 
                                color="{{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM ? 'gray' : 'warning' }}" 
                                size="sm" 
                                class="w-full"
                                :disabled="$subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM"
                            >
                                {{ $subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM ? 'Выбран' : 'Перейти на Premium' }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- ERIP Payment Instruction --}}
            <x-filament::section icon="heroicon-o-credit-card">
                <x-slot name="heading">
                    Инструкция по оплате подписки через ЕРИП
                </x-slot>
                <x-slot name="description">
                    Оплата производится официально и напрямую на расчётный счёт платформы Edusfera
                </x-slot>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800 space-y-4">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 font-medium">1. Дерево услуг в ЕРИП:</div>
                            <div class="text-sm font-bold mt-1 text-gray-900 dark:text-white">
                                Образование и развитие → Информационные услуги → Эдусфера
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 font-medium">2. Ваш номер абонента (лицевой счёт):</div>
                            <div class="text-base font-mono font-black mt-1 text-primary-600 dark:text-primary-400">
                                EDU{{ sprintf('%05d', auth()->id()) }}
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 font-medium">3. Сумма к оплате:</div>
                            <div class="text-base font-bold mt-1 text-success-600 dark:text-success-400">
                                {{ $subscription->plan->monthlyPriceByn() }} BYN
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 pt-2">
                        <span class="w-2 h-2 rounded-full bg-success-500"></span>
                        Платеж зачисляется автоматически в течение нескольких секунд после подтверждения в интернет-банке.
                    </div>
                </div>
            </x-filament::section>

            {{-- Invoices History Table --}}
            <x-filament::section icon="heroicon-o-document-text">
                <x-slot name="heading">
                    История счетов и квитанций
                </x-slot>

                <div class="overflow-x-auto mt-2">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-500 uppercase bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3">Номер счёта</th>
                                <th class="px-4 py-3">Тариф</th>
                                <th class="px-4 py-3">Сумма</th>
                                <th class="px-4 py-3">Срок оплаты</th>
                                <th class="px-4 py-3">Статус</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3 font-mono font-bold">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3">{{ $invoice->plan->title() }} ({{ $invoice->period_months }} мес)</td>
                                    <td class="px-4 py-3 font-bold">{{ $invoice->amountByn() }} BYN</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $invoice->due_date->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3">
                                        @if($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PAID)
                                            <span class="px-2 py-0.5 rounded-full bg-success-500/10 text-success-500 font-bold">Оплачен</span>
                                        @elseif($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PENDING)
                                            <span class="px-2 py-0.5 rounded-full bg-warning-500/10 text-warning-500 font-bold">Ожидает оплаты</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-gray-500/10 text-gray-500 font-bold">{{ $invoice->status->title() }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        Счетов пока нет. Первый месяц использования платформы бесплатный.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
