<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/services')]
class ServiceApiController extends AbstractController
{
    private $entityManager;
    private $serviceRepository;
    private $providerRepository;
    private $serializer;
    private $cache;
    private $mailer;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ServiceRepository $serviceRepository,
        ProviderRepository $providerRepository,
        SerializerInterface $serializer,
        CacheInterface $cache,
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->serviceRepository = $serviceRepository;
        $this->providerRepository = $providerRepository;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    #[Route('', name: 'get_all_services', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $cacheKey = 'services_list';
        
        $data = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(3600); // кэш на 1 час
            $services = $this->serviceRepository->findAll();
            return $this->serializer->serialize($services, 'json', ['groups' => 'service']);
        });

        $this->logger->info('Retrieved all services');
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create_service', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $provider = $this->providerRepository->find($data['provider_id']);
        if (!$provider) {
            $this->logger->error('Provider not found', ['provider_id' => $data['provider_id']]);
            return new JsonResponse(['message' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $service = new Service();
        $service->setName($data['name']);
        $service->setDescription($data['description']);
        $service->setPrice($data['price']);
        $service->setProvider($provider);
        $service->setCreatedAt(new \DateTime());
        $service->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($service);
        $this->entityManager->flush();
        
        // Инвалидируем кэш после создания
        $this->cache->delete('services_list');
        
        // Отправка email-уведомления
        $email = (new Email())
            ->from('noreply@example.com')
            ->to('admin@example.com') // Заменить на нужный email
            ->subject('Service Created')
            ->text('A new service has been successfully created!');

        $this->mailer->send($email);
        
        $this->logger->info('Service created', ['service_id' => $service->getId()]);
        return new JsonResponse(['message' => 'Service created!'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update_service', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (!$service) {
            $this->logger->error('Service not found', ['service_id' => $id]);
            return new JsonResponse(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['provider_id'])) {
            $provider = $this->providerRepository->find($data['provider_id']);
            if (!$provider) {
                $this->logger->error('Provider not found', ['provider_id' => $data['provider_id']]);
                return new JsonResponse(['message' => 'Provider not found'], Response::HTTP_NOT_FOUND);
            }
            $service->setProvider($provider);
        }
        
        $service->setName($data['name'] ?? $service->getName());
        $service->setDescription($data['description'] ?? $service->getDescription());
        $service->setPrice($data['price'] ?? $service->getPrice());
        $service->setUpdatedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        // Инвалидируем кэш после обновления
        $this->cache->delete('services_list');
        
        // Отправка email-уведомления
        $email = (new Email())
            ->from('noreply@example.com')
            ->to('admin@example.com') // Заменить на нужный email
            ->subject('Service Updated')
            ->text('The service has been successfully updated!');

        $this->mailer->send($email);
        
        $this->logger->info('Service updated', ['service_id' => $service->getId()]);
        return new JsonResponse(['message' => 'Service updated!']);
    }

    #[Route('/{id}', name: 'delete_service', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (!$service) {
            $this->logger->error('Service not found', ['service_id' => $id]);
            return new JsonResponse(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($service);
        $this->entityManager->flush();
        
        // Инвалидируем кэш после удаления
        $this->cache->delete('services_list');
        
        $this->logger->info('Service deleted', ['service_id' => $id]);
        return new JsonResponse(['message' => 'Service deleted']);
    }

    #[Route('/{id}', name: 'get_service', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $cacheKey = 'service_' . $id;
        
        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            $service = $this->serviceRepository->find($id);
            
            if (!$service) {
                throw new \Exception('Service not found');
            }
            
            return $this->serializer->serialize($service, 'json', ['groups' => 'service']);
        });

        $this->logger->info('Retrieved service', ['service_id' => $id]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
