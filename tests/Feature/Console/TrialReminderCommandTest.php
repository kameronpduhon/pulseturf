<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Notifications\TrialEndingNotification;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialLastDayNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for the TrialReminderCommand (trial:reminders).
 *
 * The command sends three targeted notifications:
 *   - TrialEndingNotification  → trial ends exactly 2 days from today
 *   - TrialLastDayNotification → trial ends exactly 1 day from today
 *   - TrialExpiredNotification → trial ends today (same calendar date)
 *
 * Users with an active Cashier subscription are excluded regardless of
 * their trial_ends_at value.
 */
class TrialReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user whose trial ends on the given absolute date (start of day).
     */
    private function userWithTrialEndingOn(\Carbon\Carbon $date): User
    {
        return User::factory()->create([
            'trial_ends_at' => $date->copy()->startOfDay(),
        ]);
    }

    /**
     * Attach a Cashier subscription to a user.
     */
    private function attachSubscription(User $user): void
    {
        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);
    }

    // -------------------------------------------------------------------------
    // TrialEndingNotification (2 days before expiry)
    // -------------------------------------------------------------------------

    public function test_sends_trial_ending_notification_to_user_expiring_in_two_days(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDays(2));

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertSentTo($user, TrialEndingNotification::class);
    }

    public function test_does_not_send_trial_ending_notification_to_subscribed_user(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDays(2));
        $this->attachSubscription($user);

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialEndingNotification::class);
    }

    public function test_does_not_send_trial_ending_notification_to_user_expiring_in_three_days(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDays(3));

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialEndingNotification::class);
    }

    // -------------------------------------------------------------------------
    // TrialLastDayNotification (1 day before expiry)
    // -------------------------------------------------------------------------

    public function test_sends_trial_last_day_notification_to_user_expiring_tomorrow(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDay());

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertSentTo($user, TrialLastDayNotification::class);
    }

    public function test_does_not_send_trial_last_day_notification_to_subscribed_user(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDay());
        $this->attachSubscription($user);

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialLastDayNotification::class);
    }

    public function test_does_not_send_trial_last_day_notification_to_user_expiring_in_two_days(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDays(2));

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialLastDayNotification::class);
    }

    // -------------------------------------------------------------------------
    // TrialExpiredNotification (day of expiry)
    // -------------------------------------------------------------------------

    public function test_sends_trial_expired_notification_to_user_whose_trial_ends_today(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today());

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertSentTo($user, TrialExpiredNotification::class);
    }

    public function test_does_not_send_trial_expired_notification_to_subscribed_user(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today());
        $this->attachSubscription($user);

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialExpiredNotification::class);
    }

    public function test_does_not_send_trial_expired_notification_to_user_expiring_tomorrow(): void
    {
        Notification::fake();

        $user = $this->userWithTrialEndingOn(today()->addDay());

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialExpiredNotification::class);
    }

    // -------------------------------------------------------------------------
    // No notifications for unrelated users
    // -------------------------------------------------------------------------

    public function test_does_not_send_any_notification_to_user_on_active_trial_far_from_expiry(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(10),
        ]);

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialEndingNotification::class);
        Notification::assertNotSentTo($user, TrialLastDayNotification::class);
        Notification::assertNotSentTo($user, TrialExpiredNotification::class);
    }

    public function test_does_not_send_any_notification_to_user_with_no_trial(): void
    {
        Notification::fake();

        $user = User::factory()->create(['trial_ends_at' => null]);

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertNotSentTo($user, TrialEndingNotification::class);
        Notification::assertNotSentTo($user, TrialLastDayNotification::class);
        Notification::assertNotSentTo($user, TrialExpiredNotification::class);
    }

    // -------------------------------------------------------------------------
    // Multiple users — only the right ones are notified
    // -------------------------------------------------------------------------

    public function test_sends_correct_notifications_to_multiple_users_across_stages(): void
    {
        Notification::fake();

        $twoDay  = $this->userWithTrialEndingOn(today()->addDays(2));
        $oneDay  = $this->userWithTrialEndingOn(today()->addDay());
        $expired = $this->userWithTrialEndingOn(today());
        $future  = $this->userWithTrialEndingOn(today()->addDays(5));

        $this->artisan('trial:reminders')->assertExitCode(0);

        Notification::assertSentTo($twoDay, TrialEndingNotification::class);
        Notification::assertNotSentTo($twoDay, TrialLastDayNotification::class);
        Notification::assertNotSentTo($twoDay, TrialExpiredNotification::class);

        Notification::assertSentTo($oneDay, TrialLastDayNotification::class);
        Notification::assertNotSentTo($oneDay, TrialEndingNotification::class);
        Notification::assertNotSentTo($oneDay, TrialExpiredNotification::class);

        Notification::assertSentTo($expired, TrialExpiredNotification::class);
        Notification::assertNotSentTo($expired, TrialEndingNotification::class);
        Notification::assertNotSentTo($expired, TrialLastDayNotification::class);

        Notification::assertNotSentTo($future, TrialEndingNotification::class);
        Notification::assertNotSentTo($future, TrialLastDayNotification::class);
        Notification::assertNotSentTo($future, TrialExpiredNotification::class);
    }

    // -------------------------------------------------------------------------
    // Command output
    // -------------------------------------------------------------------------

    public function test_command_outputs_count_of_sent_notifications(): void
    {
        $this->userWithTrialEndingOn(today()->addDays(2));
        $this->userWithTrialEndingOn(today()->addDay());

        $this->artisan('trial:reminders')
            ->expectsOutputToContain('Sent 2 trial reminder(s).')
            ->assertExitCode(0);
    }

    public function test_command_outputs_zero_when_no_users_match(): void
    {
        // No users in DB
        $this->artisan('trial:reminders')
            ->expectsOutputToContain('Sent 0 trial reminder(s).')
            ->assertExitCode(0);
    }
}
