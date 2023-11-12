<?php

namespace Cocoon\Http;

use Cocoon\Control\Validator;
use Cocoon\Http\Facades\Response;
use Cocoon\Http\Traits\ReturnInputAndErrorData;
use Psr\Http\Message\ServerRequestInterface;

class HttpRequest
{
    private $request;

    use ReturnInputAndErrorData;

    public function __construct()
    {
        $this->request = ServerRequest::init();
    }

    public function query($key)
    {
        return $this->request->getQueryParams()[$key];
    }

    public function file($key)
    {
        return $this->request->getUploadedFiles()[$key];
    }

    public function all(): array
    {
        return array_merge($this->request->getParsedBody(), $this->request->getUploadedFiles());
    }

    public function input($key)
    {
        return $this->request->getParsedBody()[$key];
    }

    public function only($keys = [])
    {
        $request = $this->request->getParsedBody();
        if (is_array($keys)) {
            $arr = [];
            foreach ($keys as $value) {
                $arr[$value] = $request[$value];
            }
            return $arr;
        }
    }

    public function validate($rules = [], $messages = null)
    {
        $valid = new Validator();
        $valid->validate($rules, $messages);
        $errors = [];
        if ($valid->fails()) {
            $errors = $valid->errors()->all();
        }
        if (count($errors) > 0) {
            $this->withErrors($errors);
            $this->withInput();
            $response = Response::redirect($this->request->getServerParams()['HTTP_REFERER'] ?? '');
            HttpResponseSend::emit($response, true);
        }
    }
    /**
     * Retourne une instance de ServerRequest pour utiliser d'autres méthodes
     *
     * @return ServerRequest
     */
    public function get(): ServerRequestInterface
    {
        return $this->request;
    }
}
