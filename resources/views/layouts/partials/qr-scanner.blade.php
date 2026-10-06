<style>
    .scan-qr {
        display: flex;
        align-items: center;
        gap: 8px;
        width: auto;
        height: auto;
        padding: 10px 14px;
        border: 0;
        color: white;
        z-index: 1000;
    }
    .scan-qr img { width: 28px; }
    #qr-scanner {
        width: min(540px, calc(100% - 32px));
        max-height: calc(100% - 32px);
        padding: 24px;
        border: 0;
        border-radius: 12px;
        color: #222;
        background: white;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }
    #qr-scanner::backdrop { background: rgba(0, 0, 0, 0.45); }
    #qr-scanner input { width: 100%; }
</style>

<button type="button" class="scan-qr" aria-haspopup="dialog" aria-controls="qr-scanner">
    <img src="{{ asset('images/qr_new.svg') }}" alt="">
    <span>Scan label</span>
</button>

<dialog id="qr-scanner" aria-labelledby="qr-scanner-title" aria-describedby="qr-scanner-help">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 id="qr-scanner-title" class="mb-0">Scan or enter a label</h5>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-close-scanner>Close</button>
    </div>
    <p id="qr-scanner-help">Scan a printed label with a connected USB/Bluetooth barcode scanner, or enter the order number or QR text below.</p>
    <form id="qr-scanner-form">
        @csrf
        <label for="scan-code">Order number or QR code</label>
        <input id="scan-code" name="code" class="form-control mb-2" type="text" placeholder="For example: QM-000123 or 123_1" autocomplete="off" spellcheck="false" required autofocus>
        <p id="qr-scanner-error" class="text-danger" role="alert" hidden></p>
        <button type="submit" class="btn btn-primary mt-2">Find order or driver</button>
    </form>
    <p class="text-muted mt-3 mb-0">To view or print an order’s QR code, open Orders → Action → View order → QR codes.</p>
</dialog>
