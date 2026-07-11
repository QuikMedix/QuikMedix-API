@extends('layouts.master')

@section('title') Chats List @endsection

@section('headerCss')
<link rel="stylesheet" href="{{ asset('css/twiliochat.css') }}">
<style>
  .chats .chat:hover {
    display:block;
    background-color:rgba(122, 111, 190, 0.6);
    transition: 0.3s;
  }
  .row {
    margin-right: 0px !important; 
    margin-left: 0px !important;
  }
  .count_noread {
    float:right;
    width: fit-content;
    color: #fff;
    background-color: #ec536c;
    border-radius: 50%;
    font-size: 16px;
    padding: 0.25em 0.8em;
    text-align: center;
    margin-right: 15px;
  }
  .havemes {
    display:block;
    background-color:rgba(122, 111, 190, 0.4);
  }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
              <a href="/chats/user/1"><button class="btn btn-primary" style="margin-bottom:15px;">Open Chat with Support</button></a>
                <div class="chats">
                  @foreach($chats as $chat)
                    @if(!empty($chat['last_message_date']))
                    @if($chat['count_noread']>0)
                    <a href="{{$chat['link']}}" class="chat havemes">
                    @else
                    <a href="{{$chat['link']}}" class="chat">
                    @endif
                    <div class="row no-margin">
                      <div class="row no-margin message-info-row" style="width:100%;">
                        <div class="col-md-8 left-align">
                          <img class="rounded-circle header-profile-user" src="{{$chat['image']==''?'/images/users/Avatar.png':$chat['image']}}" style="float: left;margin-left: 15px;">
                          <p class="message-username">{{$chat['name']}}</p>
                        </div>
                        @if(DateTime::createFromFormat("Y-m-d H:i:s", $chat['last_message_date'])->format("Y-m-d")==date_create()->format("Y-m-d"))
                          <div class="col-md-4 right-align"><span class="message-date">{{"Today".DateTime::createFromFormat("Y-m-d H:i:s", $chat['last_message_date'])->format(" - h:i A")}}</span></div>
                        @else
                          <div class="col-md-4 right-align"><span class="message-date">{{DateTime::createFromFormat("Y-m-d H:i:s", $chat['last_message_date'])->format("F j - h:i A")}}</span></div>
                        @endif
                      </div>
                      <br>
                      <div class="row no-margin message-info-row" style="width:100%;">
                        <div class="col-11 left-align" style="padding-left:60px !important;">
                          <p class="message-body" style="margin-left: 0px;">{{$chat['last_message_body']}}</p>
                        </div>
                        <div class="col-1 right-align">
                          <div class="count_noread">{{$chat['count_noread']}}</div>
                        </div>
                      </div>
                    </div>
                    </a>
                    <hr class="no-margin">
                    @endif
                  @endforeach
                </div>
            </div>
        </div>
    </div>
    @csrf
</div>

@endsection

@section('footerScript')

@endsection