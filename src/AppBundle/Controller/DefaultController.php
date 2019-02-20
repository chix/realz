<?php

declare(strict_types=1);

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

final class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, KernelInterface $kernel): Response
    {
        return new JsonResponse(['Ahoj', 'Hello', 'Bonjour', 'Nihao', '...']);
        /*
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($kernel->getRootDir().'/..').DIRECTORY_SEPARATOR,
        ]);
        */
    }
}
