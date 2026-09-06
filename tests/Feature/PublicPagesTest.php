<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_information_pages_are_accessible(): void
    {
        $this->get(route('legal.offer'))->assertOk()->assertSee('Публичная оферта');
        $this->get(route('legal.refund'))->assertOk()->assertSee('Правила возврата');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Политика конфиденциальности');
        $this->get(route('contacts'))->assertOk()->assertSee('Контакты и поддержка');
    }

    public function test_for_tutors_landing_page_renders_with_warm_brutalism_and_conversion_elements(): void
    {
        $response = $this->get(route('for-tutors'));
        $response->assertOk();

        // Check key headlines and value reframes
        $response->assertSee('Больше не ищите', false);
        $response->assertSee('Преподавайте', false);
        $response->assertSee('Подписка стоит как <strong>один ваш урок</strong>', false);
        $response->assertSee('Калькулятор', false);
        $response->assertSee('потерь', false);
        
        // Check 3 pain points
        $response->assertSee('Пустые окна и сорванные уроки', false);
        $response->assertSee('Рутина съедает вечера', false);
        $response->assertSee('Вы — один из сотни на досках', false);

        // Check value stack & pricing
        $response->assertSee('Что вы получаете в тарифе', false);
        $response->assertSee('380+ BYN / мес', false);
        $response->assertSee('Basic');
        $response->assertSee('Pro');
        $response->assertSee('Premium');

        // Check registration CTA links with plan query params
        $response->assertSee('/register?role=tutor&plan=basic', false);
        $response->assertSee('/register?role=tutor&plan=pro', false);
        $response->assertSee('/register?role=tutor&plan=premium', false);

        // Check guarantee & founder quota
        $response->assertSee('30 дней без заявок — продление 0 BYN', false);
        $response->assertSee('Осталось 38 из 50 мест', false);

        // Check anchor navigation targets
        $response->assertSee('id="pains"', false);
        $response->assertSee('id="features"', false);
        $response->assertSee('id="calculator"', false);
        $response->assertSee('id="pricing"', false);
        $response->assertSee('id="faq"', false);

        // Check calculator elements and interactive markup
        $response->assertSee('wb-slider', false);
        $response->assertSee('x-model.number="rate"', false);
        $response->assertSee('x-model.number="canceledPerMonth"', false);
        $response->assertSee('1 260 BYN', false);
        $response->assertSee('+ 780 BYN', false);

        // Check yearly discount pricing breakdown
        $response->assertSee('−20%', false);
        $response->assertSee('192 BYN / год при оплате за год', false);
        $response->assertSee('384 BYN / год при оплате за год', false);
        $response->assertSee('576 BYN / год при оплате за год', false);

        // Check FAQ questions and ARIA accordion structure
        $response->assertSee('Зачем платить подписку, если есть бесплатные доски объявлений?');
        $response->assertSee('У меня уже есть ученики по сарафанному радио. Зачем мне Edusfera?');
        $response->assertSee('Не дорого ли платить каждый месяц?');
        $response->assertSee('Что делать, если за месяц не поступит ни одной заявки?');
        $response->assertSee('id="faq-btn-1"', false);
        $response->assertSee('aria-controls="faq-answer-1"', false);
        $response->assertSee('id="faq-answer-1"', false);
        $response->assertSee('role="region"', false);

        // Check slider accessibility & IDs
        $response->assertSee('id="tutor-rate-slider"', false);
        $response->assertSee('id="tutor-canceled-slider"', false);
        $response->assertSee('aria-label="Ваша ставка за 1 урок в белорусских рублях"', false);
        $response->assertSee('aria-label="Количество отмененных или сорванных уроков в месяц"', false);

        // Check pricing switcher ARIA
        $response->assertSee('role="group"', false);
        $response->assertSee('aria-label="Выбор периода оплаты"', false);
        $response->assertSee(':aria-pressed="(!yearly).toString()"', false);

        // Check mobile drawer ARIA
        $response->assertSee('id="mobile-drawer"', false);
        $response->assertSee('aria-controls="mobile-drawer"', false);
    }

    public function test_for_tutors_landing_page_renders_for_authenticated_tutor(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Александр Репетиторов',
            'role' => \App\Enums\UserRole::Tutor,
        ]);

        $response = $this->actingAs($user)->get(route('for-tutors'));
        $response->assertOk();
        $response->assertSee('Александр Репетиторов');
        $response->assertSee('Личный кабинет');
        $response->assertSee('Мои финансы');
    }
}
