<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, KernelInterface $kernel)
    {
        return new JsonResponse(['Ahoj', 'Hello', 'Bonjour', 'Nihao', '...']);
        /*
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($kernel->getRootDir().'/..').DIRECTORY_SEPARATOR,
        ]);
        */
    }
}
