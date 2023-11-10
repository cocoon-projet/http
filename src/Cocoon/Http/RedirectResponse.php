<?php

namespace Cocoon\Http;

use Cocoon\Http\Facades\Session;
use Cocoon\Http\Traits\ReturnInputAndErrorData;
use Laminas\Diactoros\Response\RedirectResponse as BaseRedirectResponse;

class RedirectResponse extends BaseRedirectResponse
{
    use ReturnInputAndErrorData;

    public function __construct($url, $status = 302, array $headers = [])
    {
        if (empty($url)) {
            throw new \Exception('Redirection impossible. l\'url est vide.');
        }
        if (preg_match('/^https?/', $url)) {
            $uri = $url;
        } else {
            // TODO: voir parametre config
            //$uri = config('app.base_url') . '/' . trim($url, '/');
            $uri = trim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR) . '/' . trim($url, '/');
        }
        parent::__construct($uri, $status, $headers);
    }

    public function flash($key, $message)
    {
        Session::setFlash($key, $message);
        return $this;
    }
}
