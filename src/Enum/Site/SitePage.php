<?php
declare(strict_types=1);

namespace App\Enum\Site;

enum SitePage: string
{
    case INDEX = 'index';
    case PRIVACY_POLICY = 'privacy_policy';
    case TERMS_OF_USE = 'terms_of_use';
    case CONTACTS = 'contacts';
}