<?php

namespace App\Support;

use Twilio\Exceptions\RestException;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\ChatGrant;
use Twilio\Rest\Client;

/**
 * One-to-one chat on Twilio Conversations (classic), the product that replaced Programmable Chat.
 *
 * A conversation is only visible to its participants, so the server adds both people; the chat
 * page then finds the conversation by its name with a token for one of them.
 */
final class ChatConversations
{
    private const TOKEN_SECONDS = 3600;

    /** Whether the account, the API key that signs tokens and the Conversations service are set. */
    public function isConfigured(): bool
    {
        return config('app.twilio_sid') && config('app.twilio_apiKey') && config('app.twilio_apiSecret') && config('app.twilio_chatServiceSid');
    }

    /**
     * A token that lets the chat page read and write as this person for an hour.
     */
    public function token(string $identity): string
    {
        $token = new AccessToken(config()->string('app.twilio_sid'), config()->string('app.twilio_apiKey'), config()->string('app.twilio_apiSecret'), self::TOKEN_SECONDS, $identity);
        $grant = new ChatGrant;
        $grant->setServiceSid(config()->string('app.twilio_chatServiceSid'));
        $token->addGrant($grant);

        return $token->toJWT();
    }

    /**
     * Opens the conversation with this name for the viewer: creates it when it does not exist,
     * makes sure both people are in it, and marks everything in it read by the viewer.
     *
     * @return array{messages: list<\Twilio\Rest\Conversations\V1\Service\Conversation\MessageInstance>, other_last_read: int}
     *                                                                                                                         other_last_read is the index of the last message the other person has read, -1 for none
     */
    public function open(string $name, string $viewer, string $other): array
    {
        $service = app(Client::class)->conversations->v1->services(config()->string('app.twilio_chatServiceSid'));
        try {
            $sid = $service->conversations($name)->fetch()->sid;
        } catch (RestException $exception) {
            if ($exception->getStatusCode() !== 404) {
                throw $exception;
            }
            $sid = $service->conversations->create(['uniqueName' => $name, 'friendlyName' => $name])->sid;
        }
        $conversation = $service->conversations($sid);

        $participants = [];
        foreach ($conversation->participants->read() as $participant) {
            $participants[(string) $participant->identity] = $participant;
        }
        foreach ([$viewer, $other] as $identity) {
            $participants[$identity] ??= $conversation->participants->create(['identity' => $identity]);
        }

        $messages = $conversation->messages->read();
        if ($messages !== []) {
            $conversation->participants($participants[$viewer]->sid)->update(['lastReadMessageIndex' => end($messages)->index]);
        }

        return ['messages' => $messages, 'other_last_read' => $participants[$other]->lastReadMessageIndex ?? -1];
    }
}
