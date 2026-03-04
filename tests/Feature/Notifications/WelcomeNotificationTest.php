<?php

namespace Tests\Feature\Notifications;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for the WelcomeNotification.
 *
 * The notification is queued (ShouldQueue) and sends via the mail channel.
 * It includes the business name, competitor count, and a dashboard action link.
 */
class WelcomeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sends_via_mail_channel(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);

        $notification = new WelcomeNotification($business);

        $via = $notification->via($user);

        $this->assertContains('mail', $via);
    }

    public function test_mail_contains_business_name(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create([
            'user_id' => $user->id,
            'name' => 'Glow Aesthetics',
        ]);

        $notification = new WelcomeNotification($business);
        $mail = $notification->toMail($user);

        $rendered = $this->renderMailMessage($mail);

        $this->assertStringContainsString('Glow Aesthetics', $rendered);
    }

    public function test_mail_contains_correct_competitor_count_singular(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);
        Competitor::factory()->create(['business_id' => $business->id]);

        $notification = new WelcomeNotification($business);
        $mail = $notification->toMail($user);

        $rendered = $this->renderMailMessage($mail);

        $this->assertStringContainsString('1', $rendered);
        $this->assertStringContainsString('competitor', $rendered);
    }

    public function test_mail_contains_correct_competitor_count_plural(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);
        Competitor::factory()->count(3)->create(['business_id' => $business->id]);

        $notification = new WelcomeNotification($business);
        $mail = $notification->toMail($user);

        $rendered = $this->renderMailMessage($mail);

        $this->assertStringContainsString('3', $rendered);
        $this->assertStringContainsString('competitors', $rendered);
    }

    public function test_mail_contains_view_dashboard_action(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);

        $notification = new WelcomeNotification($business);
        $mail = $notification->toMail($user);

        // The action() call sets the action text and URL on the MailMessage
        $this->assertEquals('View Your Dashboard', $mail->actionText);
        $this->assertEquals(route('home'), $mail->actionUrl);
    }

    public function test_mail_subject_is_welcome_to_pulseturf(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);

        $notification = new WelcomeNotification($business);
        $mail = $notification->toMail($user);

        $this->assertEquals('Welcome to PulseTurf!', $mail->subject);
    }

    public function test_notification_can_be_faked_and_asserted(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $business = Business::factory()->active()->create(['user_id' => $user->id]);

        $user->notify(new WelcomeNotification($business));

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_notification_carries_correct_business_instance(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->active()->create([
            'user_id' => $user->id,
            'name' => 'Radiance Med Spa',
        ]);

        $notification = new WelcomeNotification($business);

        $this->assertSame($business->id, $notification->business->id);
        $this->assertEquals('Radiance Med Spa', $notification->business->name);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Render a MailMessage to a string for content assertions.
     * We join all intro lines to check the body text.
     */
    private function renderMailMessage(MailMessage $mail): string
    {
        return implode(' ', $mail->introLines) . ' ' . implode(' ', $mail->outroLines);
    }
}
