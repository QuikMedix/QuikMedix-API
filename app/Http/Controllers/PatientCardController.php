<?php

namespace App\Http\Controllers;

use App\Actions\SaveCardOnFile;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The page where a patient saves a card. The patient app opens it in a browser sheet through a
 * signed link from `/api/cards/add_link`, so the card number is typed into Square's own form and
 * reaches neither the app nor this server: only Square's one-time token is posted here.
 *
 * The signed link is the sign-in. It names one patient, expires after fifteen minutes, and lets
 * its holder replace that patient's saved card; it shows nothing about the account but the last
 * four digits of the card on file.
 */
class PatientCardController extends Controller
{
    public function show(string $user): View
    {
        return $this->page($this->patient($user));
    }

    public function store(Request $request, string $user, SaveCardOnFile $saveCard): View
    {
        $patient = $this->patient($user);
        $holder = ['name' => trim((string) $request->input('name')), 'address' => trim((string) $request->input('address')), 'zip' => trim((string) $request->input('zip'))];
        if (in_array('', $holder, true) || empty($request->input('card'))) {
            return $this->page($patient, error: 'Enter the card, the name on it and the billing address and ZIP code.');
        }

        $error = $saveCard->handle(
            account: PaymentAccount::query()->firstOrNew(['user_id' => $patient->id]),
            type: 'card',
            owner: ['email' => $patient->email, 'phone' => $patient->phone, 'reference' => (string) $patient->id, 'note' => 'User #'.$patient->id],
            holder: $holder,
            cardToken: (string) $request->input('card'),
        );

        // When only the refund of the confirming dollar failed, the card is on file all the same.
        return $error === null || str_starts_with($error, 'Payment Method successfully added')
            ? $this->page($patient, saved: true)
            : $this->page($patient, error: 'The card could not be saved. Check the details, or try another card.');
    }

    private function patient(string $id): User
    {
        $patient = User::query()->where('id', $id)->where('role', 'user')->first();
        abort_if($patient === null, 404);

        return $patient;
    }

    private function page(User $patient, bool $saved = false, string $error = ''): View
    {
        $account = PaymentAccount::query()->where('user_id', $patient->id)->first();

        return view('card.patient', [
            'patient' => $patient,
            'lastFour' => empty($account?->card) ? null : substr((string) $account->card, -4),
            'saved' => $saved,
            'error' => $error,
            'squareScript' => config('app.SQUARE_ENVIRONMENT') === 'sandbox'
                ? 'https://sandbox.web.squarecdn.com/v1/square.js'
                : 'https://web.squarecdn.com/v1/square.js',
        ]);
    }
}
