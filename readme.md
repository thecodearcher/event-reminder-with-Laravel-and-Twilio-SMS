## Building an event reminder with Laravel and Twilio SMS.
In our day to day activities, we might get busy, too busy to drop a message, check up on loved ones or to remember an appointment. Well, you could solve this by setting alarms…right? But what if you could build a system to send a message to your loved one or request your lunch at a set time without you having to do it.

In this tutorial, we will learn how to use Twilio’s Programmable SMS to create an SMS reminder system with Laravel. At the end of this tutorial, you would have developed a custom SMS reminder system that alerts your users via SMS of their set activity as at when due.


## Prerequisite 

In order to follow this tutorial, you will need the following:

- [C](https://getcomposer.org)[omposer](https://getcomposer.org) installed on your local machine.
- [MySQL](https://www.mysql.com/downloads/) setup on your local machine.
- Basic knowledge of the [Laravel Framework](https://laravel.com/docs/5.8/).
- A [Twilio account](https://www.twilio.com/try-twilio).


## Setting up Our Project

Let’s get started by creating a new Laravel project. We will do so using Composer. If you have Laravel installed, you can also create a new project using the [Laravel](https://laravel.com/docs/5.8/installation) command. The command below creates a new Laravel project using Composer [create-project](https://getcomposer.org/doc/03-cli.md#create-project) command:

    $ composer create-project --prefer-dist laravel/laravel event-reminder "5.8.*"

This will generate a Laravel project  (`event-reminder`) in our working directory. 

### Setting up Twilio SDK
Next, let’s install the Twilio PHP SDK which we will use for sending out SMS reminders. Open up a terminal and navigate into the just created Laravel project (`event-reminder`) and run the following command:

    $ composer require twilio/sdk

Having done that, let’s grab our Twilio credentials which we will use for setting up the SDK.  Head over to your Twilio [dashboard](https://www.twilio.com/console) and copy out both your `account_sid` and  `auth_token`:

![](https://paper-attachments.dropbox.com/s_4976E89BA020A3D2419577CAF674C172C476A70D9EEED733F5F36FF9147EEB0F_1570334783755_Group+8.png)


In addition, you need a SMS enabled number. Navigate to the [Phone Number](https://www.twilio.com/console/phone-numbers/incoming) section and also copy out your active phone number:

![](https://paper-attachments.dropbox.com/s_4976E89BA020A3D2419577CAF674C172C476A70D9EEED733F5F36FF9147EEB0F_1570334840529_Group+9.png)


Next, let’s update the environmental variables in our .`env` file located in the project root and add the following:

    TWILIO_SID = "INSERT YOUR TWILIO SID HERE"
    TWILIO_TOKEN = "INSERT YOUR TWILIO TOKEN HERE"
    TWILIO_NUMBER = "INSERT YOUR ENABLED NUMBER HERE"


## Building the Event Reminder
### Setting up database
Firstly, we need to set up our database which will be used to keep track of events. If you already know how to create a MySQL database or make use of a database management application like phpMyAdmin for managing your databases then go ahead and create a database named `sms_reminder` and skip this section. If not then follow the following steps to create a MySQL database using the [MySQL cli](https://dev.mysql.com/doc/refman/8.0/en/mysql.html).

***Note:** The following commands requires MySQL installed on your PC, you can install MySQL from the [official site](https://www.mysql.com/downloads/) for your platform.*

Open up your terminal and run this command to log in to MySQL:

    $ mysql -u {your_user_name}

***Note:*** *Add the `-p` flag if you have a password for your MySQL instance.*

Once logged in, run the following command to create a new database and also close the your session:

    mysql> create database sms_reminder;
    mysql> exit;

Next, we need to update our database credentials in our `.env`  file. Open up `.env` and make the update the following variables accordingly:

    DB_DATABASE=sms_reminder
    DB_USERNAME=root
    DB_PASSWORD=

Now that our database is in place, we need to make a [migration](https://laravel.com/docs/5.8/migrations). We can do that by running this in the terminal. 

    $ php artisan make:migration create_reminders_table

This will generate a migration file found in the `database/migrations` directory.
Now open up the project in your favourite text editor/IDE so we can make necessary adjustments to it. Let’s start off by updating our migration file to include the fields needed for our table. Open up the just created migration file and make the following changes to the `up()` method:

        public function up()
        {
            Schema::create('reminders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('mobile_no');
                $table->string('timezoneoffset');
                $table->text('message');
                $table->timestamps();
            });
        }

This means we have a database table named `reminders` expecting to have the `id`, `name`, `mobile_no`, `timezoneoffset` and `message` fields. Laravel automatically adds the  `created_at` and `updated_at` fields for us using the `$table->timestamps()`. To effect these changes, we have to run the migration command in the terminal:

    $ php artisan migrate

This will create the table on our database with the fields mentioned above.
Now let’s also generate a [model](https://laravel.com/docs/5.8/eloquent#introduction) for our table. [Eloquent model](https://laravel.com/docs/5.8/eloquent#introduction) makes it easier to query our database without having to write raw sql queries. Run the following command to generate a model for our reminds table: 

    $ php artisan make:model Reminder

This will generate a file named `Reminder.php` in the `app/` directory.

## Creating Reminders

We have successfully set up our database and also migrated our tables, now let’s write out our logic for getting and storing a user’s reminder.  Next, let’s generate a [controller](https://laravel.com/docs/5.8/controllers) which we will use for handling requests in our application. Open up a terminal in the project directory and run the following command:

    $ php artisan make:controller ReminderController

Now, open up `app/Http/Controllers/ReminderController.php` which was just generated by the above command and make the following changes:

    <?php
    namespace App\Http\Controllers;
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
            return view('welcome', ['success' => "Event reminder for {$reminder->timezoneoffset} set"]);
        }
    }
    

The function created above receives the needed data to create an event from the `$request` body and then [validates](https://laravel.com/docs/5.8/validation#introduction) the data using the `request→validate()` method. After which, the validate data is then stored in our database. 

***Note:** [Carbon](https://carbon.nesbot.com/) is used to convert the `time` and `date` from the user input into a proper datetime stamp.*

## Scheduling the SMS alerts

Now we have been able to save details for an event let’s proceed to actually sending out the SMS at due time. To do this, we will make use of [Laravel artisan command](https://laravel.com/docs/5.8/artisan#generating-commands) for writing a custom command to send sms to users if the time set for an event is equal or greater than the current time and we will have to run this command every second to ensure we don’t delay on sending out the SMS as at when due. Tasks like this which requires running a command at intervals can easily be done with a [Cron job](https://laravel.com/docs/5.8/scheduling) but unfortunately, cron jobs only support scheduling tasks at a minimum of one minute (60 seconds) interval, hence we can’t really make use of it in this use case. The only way to successfully run our command as a [daemon](https://en.wikipedia.org/wiki/Daemon_(computing)) i.e we will wrap our command logic in an infinite `while`  loop and then using the PHP `[sleep()](https://www.php.net/manual/en/function.sleep.php)`  method we will delay execution of the loop for one second at every iteration.

Now, let’s proceed to create our custom command. Open up your terminal in the project directory and run the following command to create a custom artisan command:

    $ php artisan make:command SendReminder

This will generate a file (`app/Console/Commands/SendReminder.php`) now open up the file and make the following changes:

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
    

Let’s take a closer look at what is happening in the  `handle` function. We wrapped our entire logic in a `while()` loop then, we initialized the Twilio SDK using our Twilio credentials which we stored as environmental variables in the earlier section of this article. After which we then get the current datetime in our time zone (`Africa/Lagos 🇳🇬`) which was also used to store the datetime in our database. Next, we query the database for reminders equal to the current time and it’s `status`  is set to `pending` we then proceed to send out each reminder which was gotten from the query while also updating their status to `sent`.  After successful execution of this circle, we then delay the code execution by `one second`  using the `sleep(1)` method called at the end of the loop. This will ensure our loop is delayed for at least a second before continuing the loop iteration. Now when ever we run our command using the `$signature`, it will stay keep running our logic for every second until it is destroyed.

## Setting Up Our User Interface

At this point, we have successfully written out our logic for both storing and also sending out our event reminders now let’s build our user interface which our users will use to send needed information for creating a reminder.  We will make use of [Bootstrap](http://getbootstrap.com) to ease styling of our forms. Open up `/resources/views/welcome.blade.php` and make the following changes:

    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
        <!-- Bootstrap core CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
            integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    
    </head>
    <body>
        <div class="content my-5">
            <div class="container">
                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="row justify-content-center">
                    <div class="col-8">
                        <h4 class="mb-3" style="text-align: center">SMS Reminder Form</h4>
                        <form method="post" action="{{route('add-reminder')}}">
                            @csrf
                            <label for="mobile_no">Phone number</label>
                            <div class="input-group">
                                <input type="tel" class="form-control" name="mobile_no" id="mobile_no"
                                    placeholder="Phone number" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 my-3">
                                    <label for="date">Notification Date</label>
                                    <input type="date" class="form-control" name="date" id="date" required>
                                </div>
                                <div class="col-md-6 my-3">
                                    <label for="time">Notification Time</label>
                                    <input type="time" class="form-control" name="time" id="time" required>
                                </div>
                            </div>
                            <div class="my-3">
                                <label for="message">Reminder Message</label>
                                <textarea class="form-control" name="message" rows="5" id="message"
                                    placeholder="Type in your reminder message here" required></textarea>
                            </div>
                            <hr class="mb-4">
                            <button class="btn btn-primary btn-lg btn-block" type="submit">Set Reminder</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    

With our form set, and pointed towards the [named route](https://laravel.com/docs/6.x/routing#named-routes) `add-reminder` let’s proceed to create the route. Now open up `routes/web.php`  and make the following changes:

    <?php
    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | contains the "web" middleware group. Now create something great!
    |
     */
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');
    
    Route::post('/create-reminder', 'ReminderController@create')->name('add-reminder');
    
## Testing our Application

Well that has been a lot of coding! Now let’s actually test our application. Open up a terminal in the project directory and run the following command to start the Laravel application:

    $ php artisan serve

You should see a message printed out on your terminal with the URL to your application, typically `http://127.0.0.1:8000`. Now open up your browser and navigate to the specified URL, you should be greeted with something similar to this:

![](https://paper-attachments.dropbox.com/s_4976E89BA020A3D2419577CAF674C172C476A70D9EEED733F5F36FF9147EEB0F_1570332605741_Screenshot+from+2019-10-06+04-29-55.png)

Next, with your Laravel application still running, let’s start our custom daemon by running our custom artisan command. Open up another instance of your terminal (still in the project directory) and run the custom artisan command using it’s defined `$signature`:

    $ php artisan reminder:send

You should see a message “*Reminder Daemon Started*” printed out on your terminal.
Now, head back to your browser and create a custom reminder, setting the `phone number` the reminder should be sent to, `event date`, `event time` and `message` of the reminder. And at due date and time you should receive and SMS alert with your `message`.

***Note:** When entering the `phone number`, it is imperative to enter the country code alongside it.*

![](https://paper-attachments.dropbox.com/s_4976E89BA020A3D2419577CAF674C172C476A70D9EEED733F5F36FF9147EEB0F_1570336783535_Peek+2019-10-06+05-37.gif)
*Gif showing how to create a reminder* 

![](https://paper-attachments.dropbox.com/s_4976E89BA020A3D2419577CAF674C172C476A70D9EEED733F5F36FF9147EEB0F_1570336986123_Slice+1.png)


## Conclusion.

Awesome! At this point you should have a working SMS based reminder system. And also, you have learnt how to make use of Twilio’s programmable SMS for sending out SMS from your Laravel application and you also saw how to create a custom artisan command in a Laravel application and how to run it as a daemon. 

If you will like to take a look at the complete source code for this tutorial, you can find it on [Github](https://github.com/thecodearcher/event-reminder-with-Laravel-and-Twilio-SMS). 

You can also take this further by running your daemon using [Supervisor](http://supervisord.org/). Supervisor can be used to monitor your daemon and to restart your daemon if it fails.

I’d love to answer any question(s) you might have concerning this tutorial. You can reach me via

- Email: [brian.iyoha@gmail.com](mailto:brian.iyoha@gmail.com)
- Twitter: [thecodearcher](https://twitter.com/thecodearcher)
- GitHub: [thecodearcher](https://github.com/thecodearcher)
