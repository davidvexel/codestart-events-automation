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
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        Select the campaign folder
                        @foreach($folders as $folder)
                            <a class="btn btn-block btn-light" href="/mailchimp/create/{{$folder['id']}}">{{$folder['name']}}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
