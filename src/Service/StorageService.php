<?php

namespace App\Service;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StorageService
{
    private const COOKIE_NAME = 'storage_type';
    private const DEFAULT_STORAGE = 'csv';
    private const ALLOWED_TYPES = ['csv', 'db'];
    private const COOKIE_LIFETIME = 2592000; // 30 дней в секундах

    public function getStorageType(Request $request): string
    {
        return $request->cookies->get(self::COOKIE_NAME, self::DEFAULT_STORAGE);
    }

    public function setStorageType(Response $response, string $type): Response
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Недопустимый тип хранилища');
        }

        $cookie = new Cookie(
            self::COOKIE_NAME,
            $type,
            time() + self::COOKIE_LIFETIME,
            '/',
            null,
            false,
            true
        );

        $response->headers->setCookie($cookie);
        return $response;
    }
}