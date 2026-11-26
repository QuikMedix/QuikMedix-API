<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Card for co-pays · QuikMedix</title>
<style>
  :root { --red: #ba052b; --ink: #1a1f29; --muted: #6e7582; --border: #e6e8ed; --blush: #fff0f2; --green: #0f754f; }
  * { box-sizing: border-box; }
  body { margin: 0; padding: 24px 22px 40px; background: #f7f7fa; color: var(--ink); font: 16px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  main { max-width: 460px; margin: 0 auto; }
  h1 { font-size: 28px; line-height: 1.2; margin: 0 0 6px; }
  p { margin: 0 0 16px; color: var(--muted); }
  .card { background: #fff; border: 1px solid var(--border); border-radius: 22px; padding: 20px; margin-top: 18px; }
  label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin: 14px 0 6px; }
  input[type=text] { width: 100%; padding: 13px 14px; border: 1px solid var(--border); border-radius: 12px; font: inherit; color: var(--ink); background: #fff; }
  input[type=text]:focus { outline: 2px solid var(--red); outline-offset: 1px; }
  button { width: 100%; min-height: 52px; margin-top: 20px; border: 0; border-radius: 14px; background: var(--red); color: #fff; font: 600 16px/1 inherit; }
  button[disabled] { opacity: .55; }
  .notice { border-radius: 14px; padding: 14px 16px; margin-top: 18px; font-weight: 600; }
  .notice.ok { background: #e9f6f0; color: var(--green); }
  .notice.bad { background: var(--blush); color: var(--red); }
  .on-file { font-weight: 600; color: var(--ink); }
  #card-container { min-height: 90px; margin-top: 4px; }
</style>
</head>
<body>
<main>
  <h1>Card for co-pays</h1>
  <p>Your card is typed into Square's secure form. QuikMedix does not see or keep the card number.</p>

  @if($saved)
    <div class="notice ok" role="status">Card saved. You can close this page and go back to the app.</div>
  @endif
  @if($error !== '')
    <div class="notice bad" role="alert">{{ $error }}</div>
  @endif
  <div class="notice bad" role="alert" id="form-problem" hidden></div>

  @if($lastFour !== null)
    <div class="card"><span class="on-file">Card on file: •••• {{ $lastFour }}</span></div>
  @endif

  @unless($saved)
  <form method="POST" action="{{ request()->getRequestUri() }}" id="save-card" class="card">
    @csrf
    <input type="hidden" name="card" id="card-token">
    <label>Card</label>
    <div id="card-container"></div>
    <label for="name">Name on the card</label>
    <input type="text" id="name" name="name" autocomplete="cc-name" required value="{{ old('name', trim($patient->name.' '.$patient->last_name)) }}">
    <label for="address">Billing address</label>
    <input type="text" id="address" name="address" autocomplete="street-address" required value="{{ old('address', $patient->address) }}">
    <label for="zip">Billing ZIP code</label>
    <input type="text" id="zip" name="zip" autocomplete="postal-code" inputmode="numeric" required value="{{ old('zip', $patient->zip) }}">
    <button type="submit" id="save-button" disabled>{{ $lastFour === null ? 'Save card' : 'Replace card' }}</button>
  </form>
  <p style="margin-top:14px;font-size:13px;">Saving a card places a $1 charge to confirm it and refunds it right away.</p>
  @endunless
</main>

@unless($saved)
<script src="{{ $squareScript }}"></script>
<script>
  (async function () {
    const form = document.getElementById('save-card');
    const button = document.getElementById('save-button');
    const problem = document.getElementById('form-problem');
    const say = function (message) { problem.textContent = message; problem.hidden = false; };

    if (!window.Square) { say('The card form could not load. Check your connection and open this page again.'); return; }
    let card;
    try {
      card = await window.Square.payments(@json(config('app.SQUARE_APPLICATION_ID')), @json(config('app.SQUARE_LOCATION_ID'))).card();
      await card.attach('#card-container');
    } catch (e) {
      say('The card form could not load. Close this page and try again from the app.');
      return;
    }
    button.disabled = false;

    form.addEventListener('submit', async function (event) {
      if (document.getElementById('card-token').value) { return; }
      event.preventDefault();
      problem.hidden = true;
      button.disabled = true;
      try {
        const result = await card.tokenize();
        if (result.status !== 'OK') { throw new Error('refused'); }
        document.getElementById('card-token').value = result.token;
        form.submit();
      } catch (e) {
        button.disabled = false;
        say('Check the card number, expiry date, security code and ZIP code.');
      }
    });
  })();
</script>
@endunless
</body>
</html>
