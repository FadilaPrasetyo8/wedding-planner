<?php

namespace App\Support;

use App\Models\Wedding;
use App\Models\WeddingMember;
use LogicException;

class ActiveWeddingContext
{
    private ?Wedding $wedding = null;

    private ?WeddingMember $membership = null;

    public function set(Wedding $wedding, WeddingMember $membership): void
    {
        $this->wedding = $wedding;
        $this->membership = $membership;
    }

    public function wedding(): Wedding
    {
        return $this->wedding ?? throw new LogicException('No active wedding is available for this request.');
    }

    public function membership(): WeddingMember
    {
        return $this->membership ?? throw new LogicException('No active wedding membership is available for this request.');
    }

    public function hasWedding(): bool
    {
        return $this->wedding !== null;
    }
}
