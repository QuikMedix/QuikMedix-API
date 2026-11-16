<?php

namespace App\Actions;

use App\Models\PaymentAccount;
use App\Models\PharmacyPaymentAccount;
use App\Support\SquarePayments;
use Square\Models\Address;
use Square\Models\Card;
use Square\Models\CreateCardRequest;
use Square\Models\CreateCustomerRequest;
use Square\SquareClient;

/**
 * Saves a payment method at Square for a pharmacy or a patient.
 *
 * The first time, the owner becomes a Square customer. A card is then proved with a one-dollar
 * charge, stored on the customer, and the dollar is refunded. Each step that succeeds is written
 * to the account at once, so a later refusal does not lose it.
 */
class SaveCardOnFile
{
    private const PROOF_DOLLARS = 1;

    public function __construct(private SquareClient $client, private SquarePayments $payments) {}

    /**
     * @param  PharmacyPaymentAccount|PaymentAccount  $account  the owner's account, new or existing
     * @param  string  $type  "card" or "bank"
     * @param  array{email: ?string, phone: ?string, reference: string, note: string}  $owner
     * @param  array{name: string, address: string, zip: string}  $holder  as typed into the form
     * @param  string  $cardToken  the one-time token from Square's card form; unused for a bank account
     * @return string|null what went wrong, or null when the method was saved
     */
    public function handle(PharmacyPaymentAccount|PaymentAccount $account, string $type, array $owner, array $holder, string $cardToken): ?string
    {
        $name = explode(' ', $holder['name']) + [1 => ''];
        $address = $this->address($holder['address'], $holder['zip']);

        if (! $account->exists) {
            $body = new CreateCustomerRequest;
            $body->setGivenName($name[0]);
            $body->setFamilyName($name[1]);
            $body->setEmailAddress($owner['email']);
            $body->setAddress($address);
            $body->setPhoneNumber(str_replace([' ', '-', ')', '('], '', (string) $owner['phone']));
            $body->setReferenceId($owner['reference']);
            $body->setNote($owner['note']);
            $response = $this->client->getCustomersApi()->createCustomer($body);
            if (! $response->isSuccess()) {
                return json_encode($response->getErrors());
            }
            $account->profile_id = $response->getResult()->getCustomer()->getId();
        }
        $account->type = $type;
        $account->save();

        if ($type !== 'card') {
            return null;
        }

        $proof = $this->payments->charge($cardToken, $account->profile_id, self::PROOF_DOLLARS, 'Authorization Card');
        if (! $proof->completedOrPending()) {
            return $proof->failure();
        }
        $account->update(['payment_id' => $proof->paymentId]);

        $card = new Card;
        $card->setCardholderName($name[0].' '.$name[1]);
        $card->setBillingAddress($address);
        $card->setCustomerId($account->profile_id);
        $response = $this->client->getCardsApi()->createCard(new CreateCardRequest(SquarePayments::idempotencyKey(), $proof->paymentId, $card));
        if (! $response->isSuccess()) {
            return json_encode($response->getErrors());
        }
        $saved = $response->getResult()->getCard();
        $account->update(['payment_profile_id' => $saved->getId(), 'card' => $saved->getLast4()]);

        $refundErrors = $this->payments->refund($proof->paymentId, self::PROOF_DOLLARS);

        return $refundErrors === null ? null : 'Payment Method successfully added, but refund payment not completed. '.$refundErrors;
    }

    /**
     * The form has one address line: "street, apartment, city" and optionally a state, New York
     * when left out.
     */
    private function address(string $typed, string $zip): Address
    {
        $parts = explode(',', $typed) + [1 => '', 2 => '', 3 => 'NY'];
        $address = new Address;
        $address->setAddressLine1($parts[0]);
        $address->setAddressLine2($parts[1]);
        $address->setLocality($parts[2]);
        $address->setAdministrativeDistrictLevel1($parts[3]);
        $address->setPostalCode($zip);
        $address->setCountry('US');

        return $address;
    }
}
