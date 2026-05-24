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
}
