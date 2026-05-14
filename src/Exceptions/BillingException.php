<?php

namespace NextMigrant\Plunk\Exceptions;

/**
 * Thrown on 402 responses — billing limit exceeded or plan upgrade required.
 */
class BillingException extends PlunkException {}
