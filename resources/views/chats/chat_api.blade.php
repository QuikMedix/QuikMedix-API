<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat</title>
  <link href="{{ URL::asset('/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
  <link href="{{ URL::asset('/css/bootstrap-lib.min.css?v=quikmedix-1') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="{{ asset('css/twiliochat.css?v=quikmedix-1') }}">
  <style>
    .header-profile-user {
        height: 36px;
        width: 36px;
        background-color: #dee2e6;
    }
    .check {
        width: 13px;
    }
    .check2 {
        width: 13px;
    }
  </style>
</head>
<body>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body chat_page">
                <div id="container" class="row">
                    <div id="chat-window" class="col-md-12">
                        <div id="message-list" class="row connected">
                        @foreach($messages as $message)
                          @if($message->author==Auth::user()->id)
                          <div class="row no-margin own-message">
                          @else
                          <div class="row no-margin">
                          @endif
                            <div class="row no-margin message-info-row" style="">
                              <div class="col-md-8 left-align">
                                @if($message->author==Auth::user()->id)
                                <img class="rounded-circle header-profile-user" src="{{ url('/') }}{{Auth::user()->image==''?'/images/users/Avatar.png':Auth::user()->image}}" style="float: left;margin-left: 15px;">
                                <p class="message-username">{{Auth::user()->name.' '.Auth::user()->last_name}}</p>
                                @else
                                <img class="rounded-circle header-profile-user" src="{{ url('/') }}{{$user->image==''?'/images/users/Avatar.png':$user->image}}" style="float: left;margin-left: 15px;">
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
                              <div style="" class="col-md-12"><p class="message-body">{{$message->body}}</p><svg xmlns="http://www.w3.org/2000/svg" class="check" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
                              @if($message->index>$user_last_index)
                              <svg xmlns="http://www.w3.org/2000/svg" class="check2" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
                              @else
                              <svg xmlns="http://www.w3.org/2000/svg" class="check2 viewed" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
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
  <!-- One message, filled in by the chat script as text, never as HTML. -->
  <template id="message-template">
    <div class="row no-margin">
      <div class="row no-margin message-info-row" style="">
        <div class="col-md-8 left-align"><img class="rounded-circle header-profile-user" data-part="image" style="float: left;margin-left: 15px;"><p data-part="name" class="message-username"></p></div>
        <div class="col-md-4 right-align"><span data-part="date" class="message-date"></span></div>
      </div>
      <div class="row no-margin message-content-row">
        <div style="" class="col-md-12"><p data-part="body" class="message-body"></p><svg xmlns="http://www.w3.org/2000/svg" class="check" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg><svg xmlns="http://www.w3.org/2000/svg" class="check2" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg></div>
      </div>
    </div>
  </template>
  <script>
      window.chatPage = {
          me: @json((string) Auth::user()->id),
          other: @json((string) $user->id),
          conversation: @json($chat_name),
          viewer: { viewer: @json((string) Auth::user()->id), signature: @json(App\Support\ChatViewer::sign(Auth::user()->id)) },
          avatar: @json(url('/images/users/Avatar.png')),
          baseUrl: @json(url('/')),
      };
  </script>
  {{-- Twilio Conversations (classic) JavaScript SDK, pinned to one release and checked against its published hash. --}}
  <script src="https://sdk.twilio.com/js/conversations/releases/3.0.1/twilio-conversations.min.js"
          integrity="sha256-Zi521hD/ex09TXIw3GMW996Z/Ee8nxUY7rVNNYY/Z58="
          crossorigin="anonymous"></script>
  <script src="{{ asset('js/chat-conversation.js').'?v=1' }}"></script>
</body>
</html>