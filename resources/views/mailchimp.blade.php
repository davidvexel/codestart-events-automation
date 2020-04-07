@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><img width="30" src="https://www.stickpng.com/assets/images/58417f6ba6515b1e0ad75a2b.png" alt=""> MailChimp Generator</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-error" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ url('mailchimp') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Event Name</label>
                            <input class="form-control" type="text" name="name" placeholder="Type the event name" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date</label>
                            <datepicker
                                    name="date"
                                    :bootstrap-styling=true
                                    v-on:selected="updateTicketTime"
                                    format="MM/dd/yyyy"
                            ></datepicker>
                        </div>
                        <div class="form-group">
                            <label for="date">Time</label>
                            <div class="row no-gutters">
                                <div class="col-md-2">
                                    <select class="form-control" name="timeH" id="">
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08" selected>08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control" name="timeM" id="">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control" name="timeA" id="">
                                        <option value="AM">A.M.</option>
                                        <option value="PM" selected>P.M.</option>
                                    </select>
                                </div>
                                <div class="col-md-4 offset-2">
                                    <select class="form-control" name="timezone" id="">
                                        @foreach($timezones as $key => $timezone)
                                            <option value="{{ $key }}">{{ $timezone }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-dark btn-large btn">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
