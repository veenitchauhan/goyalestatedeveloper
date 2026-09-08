<?php

namespace App\Jobs;

use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use App\Services\EnquiryForms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyEnquiryOwner implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $enquiryId) {}

    public function handle(): void
    {
        $enquiry = Enquiry::with('owner')->find($this->enquiryId);
        if (! $enquiry || $enquiry->notified_at || ! $enquiry->owner || ! $enquiry->accessibleBy($enquiry->owner, 'view') || ! (EnquiryForms::current()['forms'][$enquiry->type]['notify_owner'] ?? false)) {
            return;
        }
        Mail::to($enquiry->owner->email)->send(new EnquiryReceived($enquiry->id));
        $enquiry->update(['notified_at' => now()]);
    }
}
