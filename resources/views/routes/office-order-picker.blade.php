<dialog id="office-order-dialog" class="office-order-dialog" aria-labelledby="office-order-title">
    <form method="dialog">
        <h5 id="office-order-title">Choose orders</h5>
        <p>Choose the deliveries for this office stop.</p>
        <fieldset>
            <legend class="sr-only">Available orders</legend>
            <div id="office-order-options" class="office-order-options"></div>
        </fieldset>
        <p id="office-order-error" class="text-danger mt-2" role="alert" hidden></p>
        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-outline-secondary mr-2" data-office-picker-cancel>Cancel</button>
            <button type="submit" class="btn btn-primary" data-office-picker-apply>Use selected orders</button>
        </div>
    </form>
</dialog>
