@extends('layouts.master')

@section('title') Import Patients @endsection

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
                                        @if($alert!='') 
                                            <div class="alert alert-danger" role="alert">{{ $alert }}</div>
                                        @endif
                                        @if($step=='1')
                                        <input type="hidden" name="step" value="1">
                                        <h2>Step 1</h2>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">File Patients (.csv)</label>
                                            <div class="col-sm-10" style="margin-bottom: 5px;">
                                                <input class="form-control" required type="file" name="file" onchange='encodeImageFileAsURL(this);' accept=".csv">
                                                <small>File cannot be larger than 10 mb</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">File Delimiter</label>
                                            <div class="col-sm-10">
                                                <select name="delimiter" class="form-control" required>
                                                    <option value="">Select delimiter</option>
                                                    <option value=",">,</option>
                                                    <option value=";">;</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Next</button>
                                        @else
                                        <input type="hidden" name="step" value="2">
                                        <input type="hidden" name="delimiter" value="{{$delimiter}}">
                                        <input type="hidden" name="src" value="{{$src}}">
                                        <h2>Step 2</h2>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column First Name</label>
                                            <div class="col-sm-10">
                                                <select name="name" class="form-control" required>
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Last Name</label>
                                            <div class="col-sm-10">
                                                <select name="last_name" class="form-control" required>
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Email</label>
                                            <div class="col-sm-10">
                                                <select name="email" class="form-control">
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Phone</label>
                                            <div class="col-sm-10">
                                                <select name="phone" class="form-control" required>
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Home Phone</label>
                                            <div class="col-sm-10">
                                                <select name="home_phone" class="form-control">
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Address</label>
                                            <div class="col-sm-10">
                                                <select name="address" class="form-control" required>
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column ZIP</label>
                                            <div class="col-sm-10">
                                                <select name="zip" class="form-control" required>
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Apartment</label>
                                            <div class="col-sm-10">
                                                <select name="apartment" class="form-control">
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label">Column Birth Date</label>
                                            <div class="col-sm-10">
                                                <select name="birth_date" class="form-control">
                                                    <option value="">-----</option>
                                                    @foreach($column as $col)
                                                        <option value="{{$col}}">{{$col}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" style="margin-top:10px;" class="btn btn-primary">Import</button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                    <script type='text/javascript'>
                    function encodeImageFileAsURL(element) {
                        if(element.files[0].size > 10097152){
                            alert("File is too big!");element.value = "";
                        }
                    }
                    </script>
                    
@endsection