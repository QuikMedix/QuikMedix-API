@extends('layouts.master')

@section('title') Add News @endsection

@section('content')
 <!-- start page title -->
                    <div class="row">

                   
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="save" value="1">
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Type News</label>
                                            <div class="col-sm-10">
                                                <select name="type" class="form-control">
                                                    <option value="info">Info</option>
                                                    <option value="warning">Warning</option>
                                                    <option value="danger">Danger</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Link (URL)</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" type="text" name="link" value="{{ $input['link'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Title</label>
                                            <div class="col-sm-10">
                                                <input class="form-control" required type="text" name="title" value="{{ $input['title'] }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Body</label>
                                            <div class="col-sm-10">
                                                <textarea name="text" id="text" cols="30" rows="5" class="form-control" required>{{ $input['text'] }}</textarea>
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <script type='text/javascript'>
                    function encodeImageFileAsURL(element) {
                        if(element.files[0].size > 2097152){
                            alert("File is too big!");element.value = "";
                        }
                    }
                    </script>
                    <script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
                    <script>
                        function initialize() {
                            var input = document.getElementById('searchTextField');
                            new google.maps.places.Autocomplete(input);
                        }
                    </script>
                    
@endsection