@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <img width="30"
                             src="https://edisgroup.org/wp-content/uploads/2019/06/eventbrite-logo-png-2.png"
                             alt=""> Eventbrite Generator
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ url('eventbrite') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">Event Name</label>
                                <input class="form-control" type="text" name="name" placeholder="Type the event name"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="date">Date</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <datepicker
                                                name="date"
                                                :bootstrap-styling=true
                                                format="MM/dd/yyyy"
                                        ></datepicker>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-control" name="timezone" id="">
                                            @foreach($timezones as $key => $timezone)
                                                <option value="{{ $key }}">{{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row no-gutters">
                                    <div class="col-md-2">
                                        Start Time
                                    </div>
                                    <div class="col-md-1">
                                        <select class="form-control" name="startTimeH" id="">
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
                                    <div class="col-md-1">
                                        <select class="form-control" name="startTimeM" id="">
                                            <option value="00">00</option>
                                            <option value="15">15</option>
                                            <option value="30">30</option>
                                            <option value="45">45</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <select class="form-control" name="startTimeA" id="">
                                            <option value="AM">A.M.</option>
                                            <option value="PM" selected>P.M.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 offset-2">
                                        End Time
                                    </div>
                                    <div class="col-md-1">
                                        <select class="form-control" name="endTimeH" id="">
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
                                    <div class="col-md-1">
                                        <select class="form-control" name="endTimeM" id="">
                                            <option value="00">00</option>
                                            <option value="15">15</option>
                                            <option value="30">30</option>
                                            <option value="45">45</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <select class="form-control" name="endTimeA" id="">
                                            <option value="AM">A.M.</option>
                                            <option value="PM" selected>P.M.</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="summary">Summary</label>
                                <textarea name="summary" id="" cols="30" rows="5" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="description">Description (HTML allowed)</label>
                                <textarea name="description" id="" cols="30" rows="10" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="order_confirmation_message">Order Confirmation Message</label>
                                <textarea name="order_confirmation_message" id="" cols="30" rows="10" class="form-control"></textarea>
                            </div>

                            <h3>Venues</h3>
                            <p>Select the venues where you want to publish this event.</p>

                            <ul class="list-group list-group-flush">
                                @foreach($venues as $venue)
                                    <li class="list-group-item">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                name="venues[]"
                                                value="{{$venue['id']}}:{{$venue['latitude']}}:{{$venue['longitude']}}:{{$venue['address']['city']}}"
                                                id="{{$venue['id']}}"
                                            >
                                            <label class="custom-control-label" for="{{$venue['id']}}">{{ $venue['address']['localized_address_display'] }}</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <button type="submit" class="btn-dark btn-large btn">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
