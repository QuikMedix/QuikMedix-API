<html>
<head>
<title>Card Add</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="/css/payment2.css">
<script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>
<script src="https://web.squarecdn.com/v1/square.js"></script>
<script type="module">
  // Checkpoint 1
  const appId = '{{config('app.SQUARE_APPLICATION_ID')}}';
  const locationId = '{{config('app.SQUARE_LOCATION_ID')}}';
  // Checkpoint 2
  async function createPayment(tokenResult) {
    $("#card-n").val(tokenResult);
    $("#payment-form").hide();
    $("#addCard").show();
    return true;
  }
  $("#type").on("change",function(){
    $("#type-n").val($(this).val());
    if($(this).val()=="card") {
      $("#payment-form").show();
      $("#addCard").hide();
    } else {
      $("#payment-form").hide();
      $("#addCard").show();
    }
  });
  async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container'); 
    return card; 
  }
  async function tokenize(paymentMethod) {
    const tokenResult = await paymentMethod.tokenize();
    if (tokenResult.status === 'OK') {
      return tokenResult.token;
    } else {
      let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
      if (tokenResult.errors) {
        errorMessage += ` and errors: ${JSON.stringify(
          tokenResult.errors
        )}`;
      }
      throw new Error(errorMessage);
    }
  }
  document.addEventListener('DOMContentLoaded', async function () {
    if (!window.Square) {
      throw new Error('Square.js failed to load properly');
    }

    const payments = window.Square.payments(appId, locationId);
    let card;
    try {
      card = await initializeCard(payments);
    } catch (e) {
      console.error('Initializing Card failed', e);
      return;
    }

    // Checkpoint 2.
    async function handlePaymentMethodSubmission(event, paymentMethod) {
      event.preventDefault();

      try {
        // disable the submit button as we await tokenization and make a
        // payment request.
        cardButton.disabled = true;
        const token = await tokenize(paymentMethod);
        const paymentResults = await createPayment(token);

        console.debug('Payment Success', paymentResults);
      } catch (e) {
        cardButton.disabled = false;
        console.error(e.message);
      }
    }

    const cardButton = document.getElementById(
      'card-button'
    );
    cardButton.addEventListener('click', async function (event) {
      await handlePaymentMethodSubmission(event, card);
    });
  });
</script>
</head>
<body>
<br>
@if(!empty($error))
<div class="alert alert-danger" role="alert">{{$error}}</div>
@endif
@if(!empty($success))
<div class="alert alert-success" role="alert">{{$success}}</div>
@endif
@if(!empty($payment_account))
@if($payment_account->type=="card")
<h2 class="mb-4">Payment Method: Card - XXXX{{substr($payment_account->card,-4)}}</h2>
@else
<h2 class="mb-4">Payment Method: Bank Account</h2>
@endif
@endif
<h2 style="margin-top:30px;">Add New Payment Method</h2>
<!--
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
-->
<div class="colums">
  <label>Select Payment Method</label>
  <select style="margin-bottom:10px;" id="type">
    <option value="">----</option>
    <option value="bank">Bank Account</option>
    <option value="card">Credit Card</option>
  </select>
  <form id="payment-form" class="payment-form" style="display:none;">
    <div id="card-container"></div>
    <button id="card-button" type="button">Authorization Card</button>
  </form>
  <div id="payment-status-container"></div>
  <form method="POST" id="addCard" style="display:none;">
    @csrf 
    <input type="hidden" name="card" required id="card-n">
    <input type="hidden" name="type" required id="type-n">
    <label>Cardholder name</label>
    <input style="margin-bottom:10px;" type="text" id="cardholder-name" required name="name" placeholder="Holder name"/>
    <label>Billing ZIP</label>
    <input style="margin-bottom:10px;" type="text" required name="zip" placeholder="Billing postal code"/>
    <label>Billing address</label>
    <input style="margin-bottom:10px;" id="searchTextField" autocomplete="off" type="text" required name="address" placeholder="Billing address"/>
    <button type="submit">Save Payment Method</button>
  </form>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key={{config('app.googlemaps_apikey')}}&region=US&language=en&libraries=places&v=weekly&callback=initialize" defer></script>
<script>
    function initialize() {
        var input = document.getElementById('searchTextField');
        new google.maps.places.Autocomplete(input);
    }
</script>
</body>
</html>