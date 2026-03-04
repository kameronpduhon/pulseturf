<?php

namespace App\Livewire;

use App\Models\Digest;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DigestHistory extends Component
{
    public function render()
    {
        $digests = Digest::where('user_id', auth()->id())
            ->where('status', 'sent')
            ->latest('sent_at')
            ->get();

        return view('livewire.digest-history', compact('digests'));
    }
}
