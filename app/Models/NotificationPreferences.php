<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BankAccount\Allocation;
use App\Models\BankAccount;
use \App\Services\External\BraintreeService;
use \Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotificationPreferences extends Model
{
    protected $table = 'notification_preferences';
    public $timestamps = false;
    protected $casts = [
        'transfer_warning_modal_dismissed' => 'boolean'
    ];

    public function account(){
        return $this->belongsTo('App\Models\Account');
    }

    public function setAssignmentReminderFrequencyAttribute($assignmentReminderFrequency)
    {
        $notificationPreferences = $this;
        $validValues = ['daily', 'weekly', 'never'];
        $isValueInvalid = !in_array($assignmentReminderFrequency, $validValues);
        if ($isValueInvalid) {
            $errorMessage = "Failed to set assignment_reminder_frequency to: {$assignmentReminderFrequency}  Valid options are: " . implode(', ', $validValues);
            throw new HttpException(422, $errorMessage);
        }
        return $notificationPreferences->attributes['assignment_reminder_frequency'] = $assignmentReminderFrequency;
    }

    public function setDefenseReminderFrequencyAttribute($defenseReminderFrequency)
    {
        $notificationPreferences = $this;
        $validValues = ['monthly', 'never'];
        $isValueInvalid = !in_array($defenseReminderFrequency, $validValues);
        if ($isValueInvalid) {
            $errorMessage = "Failed to set defense_reminder_frequency to: {$defenseReminderFrequency}  Valid options are: " . implode(', ', $validValues);
            throw new HttpException(422, $errorMessage);
        }
        return $notificationPreferences->attributes['defense_reminder_frequency'] = $defenseReminderFrequency;
    }

    public static function mergeOrCreate($payload = [])
    {
        if(isset($payload['id'])) {
            $notificationPreferences = NotificationPreferences::findOrFail($payload['id']);
        } else {
            $notificationPreferences = new NotificationPreferences();
        }

        $notificationPreferences->assignment_reminder_frequency = $payload['assignment_reminder_frequency'] ?? 'weekly';
        $notificationPreferences->defense_reminder_frequency = $payload['defense_reminder_frequency'] ?? 'never';
        $notificationPreferences->transfer_warning_modal_dismissed = $payload['transfer_warning_modal_dismissed'] ?? false;

        return $notificationPreferences;
    }

    public function optOut()
    {
        $notificationPreferences = $this;
        $notificationPreferences->assignment_reminder_frequency = 'never';
        $notificationPreferences->defense_reminder_frequency = 'never';
        $notificationPreferences->save();
    }
}
