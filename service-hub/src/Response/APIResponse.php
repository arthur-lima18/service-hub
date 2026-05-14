<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

trait APIResponse
{
    protected function success(
        SerializerInterface $serializer,
        mixed $data = null,
        ?string $group = null,
        int $status = 200
    ): JsonResponse {
        $normalizedData = $data;

        if ($data !== null) {
            $json = $serializer->serialize($data, 'json', [
                'groups' => $group
            ]);

            $normalizedData = json_decode($json);
        }

        return new JsonResponse([
            'success' => true,
            'data' => $normalizedData,
            'error' => null
        ], $status);
    }

    protected function error(
        array $errors,
        int $status = 400
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'data' => null,
            'error' => $errors
        ], $status);
    }
}