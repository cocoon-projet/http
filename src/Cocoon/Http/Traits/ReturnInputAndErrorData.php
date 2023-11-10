<?php
namespace Cocoon\Http\Traits;

use Cocoon\Http\Facades\Request;
use Cocoon\Http\Facades\Session;

trait ReturnInputAndErrorData
{
    /**
     * Enregistre les erreurs en session key = errors
     *
     * @param array $errors
     * @return RedirectResponse
     */
    public function withErrors(array $errors = [])
    {
        Session::set('errors', $errors);
        return $this;
    }
    /**
     * Enregistre les données $_POST en session pour
     * un réaffichage dans le formulaire
     */
    public function withInput()
    {
        $request = Request::getParsedbody();
        foreach ($request as $key => $value) {
            Session::setInput($key, $value);
        }
        return $this;
    }
}
