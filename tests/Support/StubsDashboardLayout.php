<?php

namespace Tests\Support;

trait StubsDashboardLayout
{
    /**
     * Replaces layouts.master with a bare shell, so a page can be rendered without the sidebar's
     * notification, balance and chat queries. The page's own sections render as usual.
     */
    protected function stubDashboardLayout(): void
    {
        $directory = sys_get_temp_dir().'/quikmedix-test-layout-'.getmypid();
        if (! is_dir($directory.'/layouts')) {
            mkdir($directory.'/layouts', 0700, true);
        }
        file_put_contents($directory.'/layouts/master.blade.php', "@yield('content')\n@yield('footerScript')");

        $this->app['view']->getFinder()->prependLocation($directory);
    }
}
