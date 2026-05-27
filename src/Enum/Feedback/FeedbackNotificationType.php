<?php
declare(strict_types=1);

namespace App\Enum\Feedback;

enum FeedbackNotificationType: int
{
    case feedback_lookup_source_about_new_feedback_lookup = 0;
    case feedback_lookup_source_about_new_feedback_search = 1;
    case feedback_lookup_target_about_new_feedback_lookup = 2;
    case feedback_search_source_about_new_feedback_search = 3;
    case feedback_search_source_about_new_feedback = 4;
    case feedback_search_target_about_new_feedback_search = 5;
    case feedback_source_about_new_feedback = 6;
    case feedback_target_about_new_feedback = 7;
    case feedback_user_subscription_owner = 8;
}
