@extends('layouts.master')

@section('title') Chat {{$user->name.' '.$user->last_name}} @endsection

@section('headerCss')
<link rel="stylesheet" href="{{ asset('css/twiliochat.css') }}">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body chat_page">
                <div id="container" class="row">
                    <div id="chat-window" class="col-md-12">
                        <div id="message-list" class="row connected">
                        @foreach($messages as $message)
                          @if($message->from==Auth::user()->id)
                          <div class="row no-margin own-message">
                          @else
                          <div class="row no-margin">
                          @endif
                            <div class="row no-margin message-info-row" style="">
                              <div class="col-md-8 left-align">
                                @if($message->from==Auth::user()->id)
                                <img class="rounded-circle header-profile-user" src="{{Auth::user()->image==''?'/images/users/Avatar.png':Auth::user()->image}}" style="float: left;margin-left: 15px;">
                                <p class="message-username">{{Auth::user()->name.' '.Auth::user()->last_name}}</p>
                                @else
                                <img class="rounded-circle header-profile-user" src="{{$user->image==''?'/images/users/Avatar.png':$user->image}}" style="float: left;margin-left: 15px;">
                                <p class="message-username">{{$user->name.' '.$user->last_name}}</p>
                                @endif
                              </div>
                              @if($message->dateCreated->setTimezone(new DateTimeZone('America/New_York'))->format("Y-m-d")==date_create()->setTimezone(new DateTimeZone('America/New_York'))->format("Y-m-d"))
                                <div class="col-md-4 right-align"><span class="message-date">{{"Today".$message->dateCreated->setTimezone(new DateTimeZone('America/New_York'))->format(" - h:i A")}}</span></div>
                              @else
                                <div class="col-md-4 right-align"><span class="message-date">{{$message->dateCreated->setTimezone(new DateTimeZone('America/New_York'))->format("F j - h:i A")}}</span></div>
                              @endif
                            </div>
                            <div class="row no-margin message-content-row">
                              <div style="" class="col-md-12"><p class="message-body">{{$message->body}}</p><i class="fas fa-check check"></i>
                              @if($message->index>$user_last_index)
                              <i class="fas fa-check check2"></i>
                              @else
                              <i class="fas fa-check check2 viewed"></i>
                              @endif
                              </div>
                            </div>
                          </div>
                        @endforeach
                        </div>
                        <div id="typing-row" class="row">
                            <p id="typing-placeholder"></p>
                        </div>
                        <div id="input-div" class="row">
                            <textarea id="input-text" disabled="true" placeholder="  Your message"></textarea>
                            <button class="btn btn-primary" type="button" style="position: absolute;right: 0;border-radius: 0px;height: inherit;" id="send_btn">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @csrf
</div>
    <!-- HTML Templates -->
    <script type="text/html" id="message-template">
      <div class="row no-margin">
        <div class="row no-margin message-info-row" style="">
          <div class="col-md-8 left-align"><img class="rounded-circle header-profile-user" data-src="userimage" style="float: left;margin-left: 15px;"><p data-content="username" class="message-username"></p></div>
          <div class="col-md-4 right-align"><span data-content="date" class="message-date"></span></div>
        </div>
        <div class="row no-margin message-content-row">
          <div style="" class="col-md-12"><p data-content="body" class="message-body"></p><i class="fas fa-check check"></i><i class="fas fa-check check2"></i></div>
        </div>
      </div>
    </script>
    <script type="text/html" id="member-notification-template">
      <p class="member-status" data-content="status"></p>
    </script>
@endsection

@section('footerScript')
<script>
    var username = '{{Auth::user()->id}}';
    var username2 = '{{$user->id}}';
    var GENERAL_CHANNEL_UNIQUE_NAME = '{{$chat_name}}';
    var GENERAL_CHANNEL_NAME = '{{$chat_name}}';
</script>
<script src="{{ asset('libs/jquery-throttle.min.js') }}"></script>
<script src="{{ asset('libs/jquery.loadTemplate-1.4.4.min.js') }}"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js"></script>
<script src="//media.twiliocdn.com/sdk/js/common/releases/0.1.7/twilio-common.min.js"></script>
<script src="https://media.twiliocdn.com/sdk/js/chat/v4.0/twilio-chat.min.js"></script>
<script src="{{ asset('js/twiliochat.js').'?v=1' }}"></script>
<script src="{{ asset('js/dateformatter.js') }}"></script>
@endsection