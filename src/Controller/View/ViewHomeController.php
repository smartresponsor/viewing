<?php

declare(strict_types=1);

namespace App\Viewing\Controller\View;

use Symfony\Component\Routing\Attribute\Route;

final readonly class ViewHomeController
{
    /**
     * Viewing is not exempt from the central view boundary. Its own controller
     * returns the same neutral View Payload that producer components must
     * return. The kernel.view subscriber performs the final render/JSON decision.
     *
     * @return array<string, mixed>
     */
    #[Route('/viewing', name: 'viewing_view_index', methods: ['GET'], defaults: ['_view_controlled' => true])]
    public function __invoke(): array
    {
        return [
            '_view' => [
                'surface' => 'view',
                'operation' => 'index',
                'intent' => 'home',
                'format' => 'auto',
                'component' => 'Viewing',
            ],
            'locations' => [
                'body' => [
                    'title' => 'Viewing / View',
                    'summary' => 'Central Symfony view boundary for Smart Responsor component payloads.',
                ],
            ],
            'data' => [
                'capability' => 'viewing',
                'boundary' => 'kernel.view',
                'defensive_boundary' => 'kernel.response',
                'controller_rendering' => 'forbidden',
            ],
            'meta' => [
                'title' => 'Viewing / View',
                'description' => 'Self-processing diagnostic home surface rendered through Viewing itself.',
            ],
        ];
    }
}
