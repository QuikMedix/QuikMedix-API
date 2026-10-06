<style>
    @page { size: 306px 406px; margin: 0; }
    .qm-label-document { display: flex; flex-wrap: wrap; gap: 16px; }
    .qm-delivery-label {
        position: relative;
        box-sizing: border-box;
        width: 306px;
        height: 406px;
        border: 2px solid #000;
        background: #fff;
        color: #000;
        font-family: Arial, "DejaVu Sans", sans-serif;
        line-height: normal;
        page-break-inside: avoid;
        page-break-before: always;
        break-inside: avoid;
    }
    .qm-delivery-label:first-child { page-break-before: auto; }
    .qm-label-header {
        position: absolute;
        top: 10px;
        left: 12px;
        right: 12px;
        height: 64px;
        border-bottom: 1px solid #000;
        text-align: center;
    }
    .qm-label-brand { position: absolute; top: 0; left: 0; right: 0; font-size: 43px; font-weight: bold; letter-spacing: -2px; line-height: 1; }
    .qm-label-tagline { position: absolute; top: 48px; left: 0; right: 0; font-size: 11px; letter-spacing: 1px; line-height: 1.1; }
    .qm-label-handling { position: absolute; top: 80px; left: 12px; right: 12px; }
    .qm-label-refrigerated { height: 28px; border: 2px solid #000; border-radius: 5px; font-size: 17px; font-weight: bold; line-height: 1.4; text-align: center; }
    .qm-label-snowflake { font-family: "DejaVu Sans", sans-serif; font-size: 22px; line-height: 1; vertical-align: middle; }
    .qm-label-temperature { position: absolute; top: 34px; left: 0; right: 0; font-size: 9px; line-height: 1.1; text-align: center; }
    .qm-label-recipient { position: absolute; top: 130px; left: 12px; right: 12px; font-size: 11px; line-height: 1; }
    .qm-label-caption { display: inline-block; padding: 2px 5px; background: #000; color: #fff; font-size: 11px; font-weight: bold; line-height: 1; }
    .qm-label-name { margin-top: 5px; font-size: 23px; font-weight: bold; line-height: 1.05; overflow-wrap: anywhere; word-wrap: break-word; }
    .qm-label-name-compact { font-size: 16px; }
    .qm-label-address { margin-top: 3px; font-size: 13px; line-height: 1.15; overflow-wrap: anywhere; word-wrap: break-word; }
    .qm-label-address-compact { font-size: 10px; line-height: 1.1; }
    .qm-label-code {
        position: absolute;
        top: 233px;
        left: 12px;
        width: 278px;
        height: 118px;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
    }
    .qm-label-qr { position: absolute; top: 0; left: 0; width: 116px; }
    .qm-label-qr img { display: block; box-sizing: content-box; width: 84px; height: 84px; padding: 16px; background: #fff; }
    .qm-label-order { position: absolute; top: 4px; bottom: 4px; left: 116px; right: 0; padding-top: 20px; padding-left: 10px; border-left: 1px solid #000; box-sizing: border-box; }
    .qm-label-order-caption { display: block; font-size: 12px; font-weight: bold; line-height: 1.1; }
    .qm-label-order-number { display: block; margin-top: 5px; font-size: 20px; font-weight: bold; line-height: 1.1; white-space: nowrap; letter-spacing: -0.5px; }
    .qm-label-order-number-compact { font-size: 16px; }
    .qm-label-scan-help { display: block; margin-top: 6px; font-size: 9px; }
    .qm-label-package { position: absolute; top: 359px; left: 12px; right: 12px; font-size: 25px; font-weight: bold; line-height: 1.1; text-align: center; white-space: nowrap; }
    .qm-label-package-compact { font-size: 22px; }
    .qm-label-signature { position: absolute; bottom: 4px; left: 12px; right: 12px; font-size: 9px; font-weight: bold; line-height: 1.1; text-align: center; }
    @media print {
        html, body { margin: 0; padding: 0; background: #fff; }
        .qm-label-document { display: block; }
        .print-controls { display: none; }
        .qm-label-caption { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
</style>
