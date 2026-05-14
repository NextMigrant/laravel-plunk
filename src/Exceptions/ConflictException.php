<?php

namespace NextMigrant\Plunk\Exceptions;

/**
 * Thrown on 409 responses — resource conflicts (e.g., duplicate email).
 */
class ConflictException extends PlunkException {}
