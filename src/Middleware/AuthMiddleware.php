<?php
namespace Logbuch\Middleware;

/**
 * AuthMiddleware — prüft, ob ein Benutzer eingeloggt ist.
 * Wird vom Router vor jedem geschützten Request aufgerufen.
 */
class AuthMiddleware
{
    /**
     * @return bool  true = Zugang erlaubt, false = nicht eingeloggt
     */
    public function handle(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}
