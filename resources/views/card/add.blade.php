<html>
<head>
<title>Authorize.net</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="/css/payment.css">
</head>
<body>
@if(!empty($error))
<div class="alert alert-danger" role="alert">{{$error}}</div>
@endif
@if(!empty($success))
<div class="alert alert-success" role="alert">{{$success}}</div>
@endif
@if(!empty($payment_account))
<h2>Your Card: XXXX{{substr($payment_account->card,-4)}}</h2>
@endif
<h2 style="margin-top:30px;">Add New Card</h2>
<div class="container">
  <div class="col1">
    <div class="card">
      <div class="front">
        <div class="type">
          <img class="bankid"/>
        </div>
        <span class="chip"></span>
        <span class="card_number">&#x25CF;&#x25CF;&#x25CF;&#x25CF; &#x25CF;&#x25CF;&#x25CF;&#x25CF; &#x25CF;&#x25CF;&#x25CF;&#x25CF; &#x25CF;&#x25CF;&#x25CF;&#x25CF; </span>
        <div class="date"><span class="date_value">MM / YYYY</span></div>
        <span class="fullname">FULL NAME</span>
      </div>
      <div class="back">
        <div class="magnetic"></div>
        <div class="bar"></div>
        <span class="seccode">&#x25CF;&#x25CF;&#x25CF;</span>
        <span class="chip"></span><span class="disclaimer">This card is property of Random Bank of Random corporation. <br> If found please return to Random Bank of Random corporation - 21968 Paris, Verdi Street, 34 </span>
      </div>
    </div>
  </div>
  <div class="col2">
    <form method="POST">
        @csrf 
    <label>Card Number</label>
    <input class="number" type="text" required name="ncard" ng-model="ncard" maxlength="19" onkeypress='return event.charCode >= 48 && event.charCode <= 57'/>
    <label>Cardholder name</label>
    <input class="inputname" type="text" required name="name" placeholder=""/>
    <label>Expiry date</label>
    <input class="expire" type="text" required name="expire" placeholder="MM / YYYY"/>
    <label>Security Number</label>
    <input class="ccv" type="text" required name="ccv" placeholder="CVC" maxlength="3" onkeypress='return event.charCode >= 48 && event.charCode <= 57'/>
    <label>Billing address</label>
    <input class="address" id="searchTextField" autocomplete="off" type="text" required name="address" placeholder="Billing address"/>
    <label>Billing zip code</label>
    <input class="address" type="text" required name="zip" placeholder="Billing zip code"/>
    <button class="buy"><i class="material-icons">lock</i> Authorize Card</button>
    </form>
  </div>
</div>
<!-- partial -->
  <script src='https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.6.1/angular.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'></script>
<script src="{{ URL::asset('/js/payment.js')}}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
<script>
    function initialize() {
        var input = document.getElementById('searchTextField');
        new google.maps.places.Autocomplete(input);
    }
</script>
</body>
</html>