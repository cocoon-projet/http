<?php
declare(strict_types=1);

namespace Cocoon\Http;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;

/**
 * Classe d'émission des réponses HTTP
 * 
 * Cette classe gère l'envoi des réponses HTTP au client en utilisant
 * l'émetteur SAPI de Laminas.
 */
class HttpResponseSend
{
    /**
     * Instance de l'émetteur
     * 
     * @var EmitterInterface|null
     */
    private static ?EmitterInterface $emitter = null;

    /**
     * Définit l'émetteur à utiliser
     * 
     * @param EmitterInterface $emitter Émetteur à utiliser
     * @return void
     */
    public static function setEmitter(EmitterInterface $emitter): void
    {
        self::$emitter = $emitter;
    }

    /**
     * Obtient l'émetteur actuel ou en crée un nouveau
     * 
     * @return EmitterInterface
     */
    public static function getEmitter(): EmitterInterface
    {
        if (self::$emitter === null) {
            self::$emitter = new SapiEmitter();
        }

        return self::$emitter;
    }

    /**
     * Émet une réponse HTTP
     *
     * @param ResponseInterface $response Réponse à émettre
     * @param bool $exit Indique s'il faut terminer le script après l'émission
     * @throws \RuntimeException Si les en-têtes ont déjà été envoyés ou si la sortie a déjà été émise
     * @return void
     */
    public static function emit(ResponseInterface $response, bool $exit = false): void
    {
        self::validateEmission();
        
        $emitter = self::getEmitter();
        $emitter->emit($response);

        if ($exit) {
            exit(0);
        }
    }

    /**
     * Valide que l'émission de la réponse est possible
     * 
     * @throws \RuntimeException Si l'émission n'est pas possible
     * @return void
     */
    protected static function validateEmission(): void
    {
        if (headers_sent($file, $line)) {
            throw new \RuntimeException(
                sprintf(
                    'Impossible d\'émettre une réponse; en-têtes déjà envoyés dans %s à la ligne %d',
                    $file,
                    $line
                )
            );
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new \RuntimeException(
                'La sortie a déjà été émise. Impossible d\'émettre une réponse. ' .
                'Assurez-vous qu\'il n\'y a pas de sortie avant l\'émission de la réponse.'
            );
        }
    }

    /**
     * Émet une réponse et termine le script
     * 
     * @param ResponseInterface $response Réponse à émettre
     * @return void
     */
    public static function emitAndExit(ResponseInterface $response): void
    {
        self::emit($response, true);
    }

    /**
     * Nettoie tous les tampons de sortie
     * 
     * @return void
     */
    public static function cleanOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
