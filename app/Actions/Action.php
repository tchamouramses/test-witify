<?php

namespace App\Actions;

/**
 * A single-purpose business action.
 *
 * Every action exposes one public handle() method whose signature is specific
 * to the action. Controllers stay thin: they validate input, call an action,
 * and build the response.
 */
interface Action {}
