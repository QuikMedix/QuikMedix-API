<?php

namespace App\Support;

/**
 * The two messages a signed-in person sees when a page is refused.
 */
class AccessMessages
{
    /** The account, or its pharmacy, is blocked or not yet approved. */
    public const ACCOUNT_INACTIVE = "We're sorry for the inconvenience, but it seems we have a little hiccup here. Our team is already on the case, working hard to get things back on track as quickly as possible.";

    /** The account's role may not use the page. The wording, spelling included, is what the pages have always shown. */
    public const NO_PERMISSION = 'Permision error.';
}
