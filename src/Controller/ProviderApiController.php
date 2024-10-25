<?php

namespace App\Controller;

use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/providers')]
class ProviderApiController extends AbstractController
{
    private $entityManager;
    private $providerRepository;
    private $serializer;
    private $cache;
    private $validator;
    private $mailer;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProviderRepository $providerRepository,
        SerializerInterface $serializer,
        CacheInterface $cache,
        ValidatorInterface $validator,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->providerRepository = $providerRepository;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->validator = $validator;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('', name: 'get_all_providers', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $cacheKey = 'providers_list';
        
        $data = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(3600); // кэш на 1 час
            $providers = $this->providerRepository->findAll();
            return $this->serializer->serialize($providers, 'json', ['groups' => 'provider']);
        });

        $this->logger->info('Retrieved all providers');
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create_provider', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $provider = new Provider();
        $provider->setName($data['name']);
        $provider->setEmail($data['email']);
        $provider->setPhone($data['phone']);
        $provider->setCreatedAt(new \DateTime());
        $provider->setUpdatedAt(new \DateTime());
        
        $errors = $this->validator->validate($provider);
        if (count($errors) > 0) {
            $this->logger->error('Validation errors occurred while creating provider', ['errors' => (string) $errors]);
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
        
        // Инвалидируем кэш после создания
        $this->cache->delete('providers_list');
        
        // Отправка email-уведомления
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($provider->getEmail())
            ->subject('Provider Created')
            ->text('Your provider has been successfully created!');

        $this->mailer->send($email);
        
        $this->logger->info('Provider created', ['provider_id' => $provider->getId()]);
        return new JsonResponse(['message' => 'Provider created!'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update_provider', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            $this->logger->error('Provider not found', ['provider_id' => $id]);
            return new JsonResponse(['message' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $provider->setName($data['name'] ?? $provider->getName());
        $provider->setEmail($data['email'] ?? $provider->getEmail());
        $provider->setPhone($data['phone'] ?? $provider->getPhone());
        $provider->setUpdatedAt(new \DateTime());

        $errors = $this->validator->validate($provider);
        if (count($errors) > 0) {
            $this->logger->error('Validation errors occurred while updating provider', ['provider_id' => $id, 'errors' => (string) $errors]);
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        // Инвалидируем кэш после обновления
        $this->cache->delete('providers_list');

        // Отправка email-уведомления
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($provider->getEmail())
            ->subject('Provider Updated')
            ->text('Your provider has been successfully updated!');

        $this->mailer->send($email);

        $this->logger->info('Provider updated', ['provider_id' => $provider->getId()]);
        return new JsonResponse(['message' => 'Provider updated!'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete_provider', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            $this->logger->error('Provider not found', ['provider_id' => $id]);
            return new JsonResponse(['message' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($provider);
        $this->entityManager->flush();

        // Инвалидируем кэш после удаления
        $this->cache->delete('providers_list');

        $this->logger->info('Provider deleted', ['provider_id' => $id]);
        return new JsonResponse(['message' => 'Provider deleted!'], Response::HTTP_OK);
    }
}
