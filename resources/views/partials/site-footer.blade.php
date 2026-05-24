<footer class="ed-footer">
    <div class="ed-footer__inner">
        <div class="ed-footer__brand">
            <span class="ed-brand" style="font-size:1.4rem;">Edusfera</span>
            <span class="ed-footer__copy">© {{ date('Y') }} Платформа для подбора репетиторов в Беларуси.</span>
        </div>
        <div class="ed-footer__links" style="display:flex;gap:2rem;flex-wrap:wrap;">
            <nav style="display:flex;flex-direction:column;gap:.45rem;">
                <strong style="font-size:.85rem;">Платформа</strong>
                <a href="{{ route('home') }}">Главная</a>
                <a href="{{ route('tutors.index') }}">Каталог</a>
                <a href="{{ route('for-tutors') }}">Преподавателям</a>
            </nav>
            <nav style="display:flex;flex-direction:column;gap:.45rem;">
                <strong style="font-size:.85rem;">Документы</strong>
                <a href="{{ route('legal.offer') }}">Оферта</a>
                <a href="{{ route('legal.refund') }}">Возвраты</a>
                <a href="{{ route('legal.privacy') }}">Конфиденциальность</a>
                <a href="{{ route('contacts') }}">Контакты</a>
            </nav>
        </div>
    </div>
</footer>
