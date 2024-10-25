<?php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProviderApiControllerTest extends WebTestCase
{
    public function testCreateProvider(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/providers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Test Provider',
                'email' => 'test@example.com',
                'phone' => '1234567890'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['message' => 'Provider created!']);
    }
}
