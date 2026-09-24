<?php

// IDE-only type hints for Intelephense. This file is never loaded at runtime.
// It covers Laravel return types that laravel-ide-helper does not document.

namespace Illuminate\Contracts\Auth {
    /**
     * auth() returns the auth manager, which proxies these calls to the default guard.
     *
     * @method \App\User|null user()
     * @method int|string|null id()
     * @method bool check()
     * @method bool guest()
     */
    interface Factory {}
}

namespace Illuminate\Contracts\View {
    /**
     * view() returns Illuminate\View\View at runtime, which has renderSections().
     *
     * @method array renderSections()
     */
    interface View {}
}
