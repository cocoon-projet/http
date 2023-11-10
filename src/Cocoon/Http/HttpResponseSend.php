<?php
namespace Cocoon\Http;

use function Http\Response\send;

class HttpResponseSend
{
    /**
     * Retourne la réponse d'une requête.
     *
     * @param $response implement ResponseInterface
     * @param bool $exit
     */
	public static function emit($response, $exit = false)
    {
        if (headers_sent()) {
            throw new \RuntimeException('Impossible d\'émettre une réponse; en-têtes déjà envoyés');
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new \RuntimeException('La sortie a déjà été émise. Imposible d\'émettre une réponse');
        }
        send($response);
        if ($exit) {
            exit();
        }
    }
}