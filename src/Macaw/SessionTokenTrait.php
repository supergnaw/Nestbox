<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait SessionTokenTrait
{
    public function session_ticket(bool $forceRefresh = false): string
    {
        if ($this->session_ticket_expired() || $forceRefresh) {
            $this->relogin_user($this->loginMethod, $this->loginOptions);
        }

        return $_SESSION[$this->macawSessionKey]["data"]["SessionTicket"];
    }

    public function session_ticket_expired(): bool
    {
        $expired = strtotime(datetime: $_SESSION["PlayFab"]->EntityToken->ToeknExpiration ?? "now");
        $rightNow = strtotime(gmdate(format: "Y-m-d\TH:i:s\Z"));

        return $rightNow >= $expired;
    }
}