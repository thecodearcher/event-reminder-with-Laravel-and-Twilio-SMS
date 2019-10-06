<?php

namespace App\Console\Commands;

use App\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Twilio\Rest\Client;

class SendReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out reminders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        print_r("Reminder Daemon Started \n");
        while (true) {
            $account_sid = getenv('TWILIO_ID');
            $account_token = getenv("TWILIO_TOKEN");
            $sending_number = getenv("TWILIO_NUMBER");
            $twilio_client = new Client($account_sid, $account_token);

            $now = Carbon::now('Africa/Lagos')->toDateTimeString();
            $reminders = Reminder::where([['timezoneoffset', '=', $now], ['status', 'pending']])->get();
            foreach ($reminders as $reminder) {
                $twilio_client->messages->create($reminder->mobile_no,
                    array("from" => $sending_number, "body" => "Reminder for: $reminder->message"));
                $reminder->status = 'sent';
                $reminder->save();
            }

            \sleep(1);
        }

    }
}
