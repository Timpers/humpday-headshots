<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Notifications\GamingSessionInvitation as GamingSessionInvitationNotification;
use Illuminate\Console\Command;

class TestGamingSessionNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gaming-session-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test gaming session invitation notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing gaming session invitation notification...');

        // Get the first user to act as the host
        $host = User::first();
        if (!$host) {
            $this->error('No users found in the database.');
            return;
        }

        // Get another user to invite
        $invitee = User::where('id', '!=', $host->id)->first();
        if (!$invitee) {
            $this->error('Need at least 2 users to test invitations.');
            return;
        }

        // Create a test gaming session
        $session = GamingSession::create([
            'host_user_id' => $host->id,
            'title' => 'Test Gaming Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addHours(2),
            'max_participants' => 4,
            'privacy' => 'public',
            'status' => 'scheduled',
        ]);

        $this->info("Created gaming session: {$session->title}");

        // Create an invitation
        $invitation = GamingSessionInvitation::create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_by_user_id' => $host->id,
        ]);

        $this->info("Created invitation for user: {$invitee->name}");

        // Send the notification
        $invitee->notify(new GamingSessionInvitationNotification($invitation));

        $this->info('Notification sent successfully!');
        $this->info('Check the Laravel logs to see the email content (since MAIL_MAILER=log)');
        $this->info('Log file: storage/logs/laravel.log');
    }
}
