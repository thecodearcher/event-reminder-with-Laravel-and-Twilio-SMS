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
