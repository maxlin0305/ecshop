<?php

namespace ChinaumsPayBundle\Interfaces;

interface ClientInterface
{
    public function request(array $params = []): ?string;

    public function genSign(): ?string;

}