<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // check api guard for unauthorized responses
        /*if(auth()->guard('api')->check()) {
            return response()->json([
             'message' => 'Sorry! This action is unauthorized'
            ],401);
        }*/

        // Batch 34: a 403 ("not allowed to do that") or 419 (Laravel's
        // "Page Expired" — a stale/missing CSRF token, usually from a
        // login or form page left open too long) used to fall straight
        // through to Laravel's generic error page, a dead end for a
        // carer or family member who has no idea what either of those
        // mean. Send them back to wherever they actually belong instead.
        // Left alone for anything expecting JSON (Livewire's own
        // requests included) so Livewire's existing page-expired
        // handling is untouched — this only covers the case that had no
        // handling at all before: a plain full-page browser navigation.
        // Batch 40: a stale form on the public shop (e.g. "Sign out" left
        // open past session expiry) goes back to that shop's home page, not
        // the staff login a shop customer has nothing to do with.
        // Batch 43b: on a shop's own web address there's no /store/{slug} in the
        // path, so take the shop from the host instead.
        $domainSlug = app()->bound('shop.domain_slug') ? app('shop.domain_slug') : null;
        if ($this->shouldRedirectHome($request, $exception) && ($domainSlug || ($request->is('store/*')
            && ($slug = $request->segment(2)) && \App\Models\Shop\ShopStore::where('slug', $slug)->exists()))) {
            $slug = $domainSlug ?? $slug;
            return redirect()->route('shop.home', $slug)
                ->with('warning', $this->redirectMessageFor($exception));
        }

        if ($this->shouldRedirectHome($request, $exception)) {
            return redirect()
                ->route($this->homeRouteNameFor($request->user()))
                ->with('warning', $this->redirectMessageFor($exception));
        }

        return parent::render($request, $exception);
    }

    protected function shouldRedirectHome($request, Throwable $exception): bool
    {
        if ($request->expectsJson()) {
            return false;
        }

        return in_array($this->statusCodeFor($exception), [403, 419], true);
    }

    protected function statusCodeFor(Throwable $exception): ?int
    {
        if ($exception instanceof TokenMismatchException) {
            return 419;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return null;
    }

    /** Carer/Admin/Manager all land on 'dashboard'; Family has its own portal; a guest goes to the login form. */
    protected function homeRouteNameFor($user): string
    {
        if (! $user) {
            return 'login';
        }

        return $user->isFamilyMember() ? 'family.portal' : 'dashboard';
    }

    protected function redirectMessageFor(Throwable $exception): string
    {
        return $this->statusCodeFor($exception) === 419
            ? __("That page had gone stale, so we've brought you back here — please try again.")
            : __("You don't have access to that page, so we've brought you back here.");
    }
}
