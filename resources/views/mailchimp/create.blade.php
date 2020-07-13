@extends('layouts.app')

@section('content')
    <form action="{{ url('mailchimp') }}" method="POST">
        @csrf
        <div class="container">
            <div class="row justify-content-center">

                <div class="col-md-4">
                    @foreach($campaigns as $campaign)
                        <div class="card mb-2" id="card_{{$campaign['settings']['title']}}">
                            <input type="hidden" name="scheduleCampaignTitle[{{$campaign['id']}}]" value="{{$campaign['settings']['title']}}">
                            <div class="card-header pr-2">
                                <input type="checkbox" name="scheduleCampaignIds[]" value="{{$campaign['id']}}"
                                       id="{{$campaign['id']}}" checked>
                                <label class="form-check-label"
                                       for="{{$campaign['id']}}">{{$campaign['settings']['title']}}</label>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <input name="scheduleCampaignsNumberOfHoursOrDays[{{$campaign['id']}}]"
                                               class="form-control" type="number"
                                               placeholder="0">
                                    </div>
                                    <div class="form-group colmd-4">
                                        <select class="form-control"
                                                name="scheduleCampaignHoursOrDays[{{$campaign['id']}}]">
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <select class="form-control"
                                                name="scheduleCampaignBeforeOrAfter[{{$campaign['id']}}]" id="">
                                            <option value="before">Before</option>
                                            <option value="after">After</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <img width="30"
                                 src="https://www.stickpng.com/assets/images/58417f6ba6515b1e0ad75a2b.png"
                                 alt=""> Event Details
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

                            <div class="form-group">
                                <label for="name">Event Name</label>
                                <input class="form-control" type="text" name="name" placeholder="Type the event name"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="date">Date</label>
                                <datepicker
                                        name="date"
                                        :bootstrap-styling=true
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
                                        <select class="form-control" name="timezone" id="" required>
                                            @foreach($timezones as $key => $timezone)
                                                <option value="{{ $key }}">{{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <h4>Search and Replace</h4>

                            <div class="searchAndReplaceFields mb-3">
                                <button type="button" class="btn btn-block btn-light rounded mb-2" id="addSearchInput">
                                    Add custom field
                                </button>
                                <div class="form-row mb-2">
                                    <div class="col">
                                        <input type="text" class="form-control" name="customFieldKeys[]"
                                               placeholder="<<Example>>">
                                    </div>
                                    <div class="col">
                                        <input type="text" class="form-control" name="customFieldValues[]"
                                               placeholder="Example Value">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn-success btn-block btn">Create</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
