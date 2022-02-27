<?php

namespace Bermuda\HTTP\Emitters;

use Psr\Http\Message\ResponseInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

final class Emitter implements EmitterInterface
{
    private array $emitters = [];
    public function __construct(private int $maxBufferLength = 8192) {
    }

    /**
     * @param EmitterInterface $emitter
     * @return $this
     */
    public function addEmitter(EmitterInterface $emitter): self
    {
        $this->emitters[$emitter::class] = $emitter;
    }
  
    /**
     * @param int $length
     * @return $this
     */
    public function setMaxBufferLength(int $length): self
    {
        $this->maxBufferLength = $length;
        return $this;
    }

    /**
     * @param EmitterInterface|string $emitter
     * @return bool
     */
    public function hasEmitter(EmitterInterface|string $emitter): bool
    {
        if (!is_string($emitter)) {
            $emitter = $emitter::class;
        }

        return isset($this->emitters[$emitter]);
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    public function emit(ResponseInterface $response): bool
    {
        foreach ($this->emitters as $emitter) {
            if ($emitter->emit($response) == true) {
                return true;
            }
        }

        if (!$response->hasHeader('Content-Disposition') && !$response->hasHeader('Content-Range')) {
            return (new SapiEmitter)->emit($response);
        }

        return (new SapiStreamEmitter($this->maxBufferLength))->emit($response);
    }
}
