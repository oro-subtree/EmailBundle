<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailActivitySearchControllerTest extends WebTestCase
{
    /** @var string */
    protected $baseUrl;

    protected function setUp()
    {
        $this->markTestSkipped('Due to BAP-8365');

        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'
            ]
        );
        $this->baseUrl = $this->getUrl('oro_api_get_email_search_relations');
    }

    public function testEmailSearch()
    {
        $entityClasses = [];

        // No search string - should return all entities:
        // 3 user loaded by data fixture + admin
        $this->client->request('GET', $this->baseUrl);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($entities);
        $this->assertCount(4, $entities);
        foreach ($entities as $entity) {
            if (!isset($entityClasses[$entity['entity']])) {
                $entityClasses[$entity['entity']] = $entity['entity'];
            }
        }
        // Check using multiple entities in from filter. Should return all entities.
        $this->client->request('GET', $this->baseUrl . sprintf('?from=%s', implode(',', $entityClasses)));
        $this->assertCount(count($entities), $this->getJsonResponseContent($this->client->getResponse(), 200));

        // Check search by email
        $this->client->request('GET', $this->baseUrl . '?email=richard_bradley@example.com');
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check search by email filtered by User entity only
        $this->client->request(
            'GET',
            $this->baseUrl . '?email=richard_bradley@example.com&from=Oro\Bundle\UserBundle\Entity\User'
        );
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $entities);

        // Check searching by non-existing email. Should return no results.
        $this->client->request('GET', $this->baseUrl . sprintf('?email=%s&page=1', 'non_existent_email@example.com'));
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($entities);
    }

    public function testEmailSearchWithPaging()
    {
        $this->client->request(
            'GET',
            $this->baseUrl . '?page=2&limit=3',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }
}
