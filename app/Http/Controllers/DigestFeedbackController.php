<?php

namespace App\Http\Controllers;

use App\Models\Digest;
use Illuminate\Http\Request;

class DigestFeedbackController extends Controller
{
    public function __invoke(Request $request, Digest $digest, string $type)
    {
        if (! in_array($type, ['positive', 'negative'], true)) {
            abort(404);
        }

        $digest->update(['feedback' => $type]);

        return view('feedback.thanks');
    }
}
