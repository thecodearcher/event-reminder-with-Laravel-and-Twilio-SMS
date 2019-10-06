<?php

namespace App\Http\Controllers;

use App\Reminder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReminderController extends Controller
{

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'mobile_no' => 'required',
            'date' => 'required|date',
            'time' => 'required',
            'message' => 'required',
        ]);

        $reminder = new Reminder();
        $reminder->mobile_no = $validatedData['mobile_no'];
        $reminder->timezoneoffset = Carbon::parse("{$validatedData['date']} {$validatedData['time']}");
        $reminder->message = $validatedData['message'];
        $reminder->save();

        return back()->with(['success' => "Event reminder for {$reminder->timezoneoffset} set"]);
    }
}
