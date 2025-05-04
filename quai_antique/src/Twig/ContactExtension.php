<?php

namespace App\Twig;

use App\Repository\ContactRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContactExtension extends AbstractExtension
{
    private ContactRepository $contactRepository;

    public function __construct(ContactRepository $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('contact_repository', [$this, 'getContactRepository']),
        ];
    }

    public function getContactRepository(): ContactRepository
    {
        return $this->contactRepository;
    }
}
