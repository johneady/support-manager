<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait ResolvesAuthenticatedUser
{
    /**
     * The currently authenticated user. Components using this trait render
     * behind the auth middleware, so the user is always present; abort()
     * throws, and stands in for the null case the static analyser cannot
     * rule out from Auth::user() alone.
     */
    protected function authenticatedUser(): User
    {
        return Auth::user() ?? abort(401);
    }
}
