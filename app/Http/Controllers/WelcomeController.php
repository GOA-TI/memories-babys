<?php

namespace App\Http\Controllers;

use App\Support\FamilySession;
use Illuminate\Http\Response;

class WelcomeController extends Controller
{
    public function markSeen(): Response
    {
        $member = FamilySession::member();
        abort_unless($member, 403);

        if ($member->welcome_seen_at === null) {
            $member->update(['welcome_seen_at' => now()]);
        }

        return response()->noContent();
    }
}
