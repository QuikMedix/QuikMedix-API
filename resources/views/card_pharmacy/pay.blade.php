<html>
<head>
<title>Pay Invoice</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="/css/payment2.css">
<script src="{{ URL::asset('/libs/jquery/jquery.min.js')}}"></script>
<script src="https://web.squarecdn.com/v1/square.js"></script>
<script type="module">
  // Checkpoint 1
  const appId = '{{config('app.SQUARE_APPLICATION_ID')}}';
  const locationId = '{{config('app.SQUARE_LOCATION_ID')}}';
  // Checkpoint 2
  async function createPayment(tokenResult,options="") {
    $("#card-n").val(tokenResult);
    $("#cardholder-name").val(options.accountHolderName);
    $("#ach-form").hide();
    $("#payment-form").hide();
    $("#addCard").submit();
    return true;
  }
  async function initializeCard(payments) {
    const card = await payments.card();
    await card.attach('#card-container');

    return card;
  }

  async function initializeACH(payments) {
    const ach = await payments.ach();
    // Note: ACH does not have an .attach(...) method
    // the ACH auth flow is triggered by .tokenize(...)
    return ach;
  }

  async function tokenize(paymentMethod, options = {}) {
    const tokenResult = await paymentMethod.tokenize(options);
    if (tokenResult.status === 'OK') {
      return tokenResult.token;
    } else {
      let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
      if (tokenResult.errors) {
        errorMessage += ` and errors: ${JSON.stringify(
          tokenResult.errors
        )}`;
      }

      throw new Error(errorMessage);
    }
  }

  function getBillingContact(form) {
    const formData = new FormData(form);
    // It is expected that the developer performs form field validation
    // which does not occur in this example.
    return {
      givenName: formData.get('givenName'),
      familyName: formData.get('familyName'),
    };
  }

  function getACHOptions(form) {
    const billingContact = getBillingContact(form);
    const accountHolderName = `${billingContact.givenName} ${billingContact.familyName}`;

    return { accountHolderName };
  }

  document.addEventListener('DOMContentLoaded', async function () {
    if (!window.Square) {
      throw new Error('Square.js failed to load properly');
    }

    let payments;
    try {
      payments = window.Square.payments(appId, locationId);
    } catch {
      const statusContainer = document.getElementById(
        'payment-status-container'
      );
      statusContainer.className = 'missing-credentials';
      statusContainer.style.visibility = 'visible';
      return;
    }

    let card;
    try {
      //card = await initializeCard(payments);
    } catch (e) {
      console.error('Initializing Card failed', e);
      return;
    }

    let ach;
    try {
      ach = await initializeACH(payments);
    } catch (e) {
      console.error('Initializing ACH failed', e);
      return;
    }

    async function handlePaymentMethodSubmission(
      event,
      paymentMethod,
      options
    ) {
      event.preventDefault();
      try {
        // disable the submit button as we await tokenization and make a payment request.
        achButton.disabled = true;
        const token = await tokenize(paymentMethod, options);
        const paymentResults = await createPayment(token,options);
        console.debug('Payment Success', paymentResults);
      } catch (e) {
        achButton.disabled = false;
        console.error(e.message);
      }
    }

    const achButton = document.getElementById('ach-button');
    achButton.addEventListener('click', async function (event) {
      const paymentForm = document.getElementById('ach-form');
      const achOptions = getACHOptions(paymentForm);
      await handlePaymentMethodSubmission(event, ach, achOptions);
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
<h2 class="mb-4">Card - XXXX{{substr($payment_account->card,-4)}}</h2>
@else
<h2 class="mb-4">Bank Account</h2>
@endif
@endif
<h2 style="margin-top:30px;">Pay Invoice</h2>
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
  <form id="ach-form">
    <fieldset class="buyer-inputs">
      <input
        type="text"
        autocomplete="given-name"
        aria-required="true"
        aria-label="First Name"
        required="required"
        placeholder="Given Name"
        name="givenName"
        spellcheck="false"
      />
      <input
        type="text"
        autocomplete="family-name"
        aria-required="true"
        aria-label="Last Name"
        required="required"
        placeholder="Family Name"
        name="familyName"
        spellcheck="false"
      />
    </fieldset>
    <button id="ach-button">Authorization Bank Account</button>
  </form>
  <form method="POST" id="addCard" style="display:none;">
    @csrf 
    <input type="hidden" name="card" required id="card-n">
    <input type="hidden" name="pay" value="1" required>
    <input type="hidden" name="invoice_id" value="{{$invoice->id}}" required>
    <label>Cardholder name</label>
    <input style="margin-bottom:10px;" type="text" id="cardholder-name" required name="name" placeholder="Cardholder name"/>
    <label>Billing ZIP</label>
    <input style="margin-bottom:10px;" type="text" required name="zip" placeholder="Billing postal code"/>
    <label>Billing address</label>
    <input style="margin-bottom:10px;" id="searchTextField" autocomplete="off" type="text" required name="address" placeholder="Billing address"/>
    <button type="submit">Pay</button>
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