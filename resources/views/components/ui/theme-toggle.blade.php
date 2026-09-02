<label class="swap swap-rotate btn btn-ghost btn-circle" title="تبديل السمة">
    <input type="checkbox" x-data="themeToggle" x-model="dark" @change="apply()" class="theme-controller" />
    <x-heroicon-o-sun class="swap-off size-5" />
    <x-heroicon-o-moon class="swap-on size-5" />
</label>
