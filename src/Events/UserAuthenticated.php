<?php

namespace ElgiborSolution\Authentication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class UserAuthenticated
{
    use Dispatchable, SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The HTTP request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
