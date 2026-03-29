<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Router;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function test_add_and_match_static_route(): void
    {
        $this->router->get('/divan', 'DivanController', 'index');
        $match = $this->router->match('GET', '/divan');
        $this->assertNotNull($match);
        $this->assertEquals('DivanController', $match['controller']);
        $this->assertEquals('index', $match['action']);
    }

    public function test_match_route_with_parameter(): void
    {
        $this->router->get('/oy/{token}', 'VoteController', 'show');
        $match = $this->router->match('GET', '/oy/abc-123-def');
        $this->assertNotNull($match);
        $this->assertEquals('VoteController', $match['controller']);
        $this->assertEquals(['token' => 'abc-123-def'], $match['params']);
    }

    public function test_no_match_returns_null(): void
    {
        $this->router->get('/divan', 'DivanController', 'index');
        $match = $this->router->match('GET', '/nonexistent');
        $this->assertNull($match);
    }

    public function test_method_mismatch_returns_null(): void
    {
        $this->router->get('/divan', 'DivanController', 'index');
        $match = $this->router->match('POST', '/divan');
        $this->assertNull($match);
    }

    public function test_post_route(): void
    {
        $this->router->post('/auth/login', 'AuthController', 'login');
        $match = $this->router->match('POST', '/auth/login');
        $this->assertNotNull($match);
        $this->assertEquals('AuthController', $match['controller']);
    }

    public function test_multiple_parameters(): void
    {
        $this->router->get('/yonetim/ballots/{ballotId}/candidates', 'BallotController', 'addCandidate');
        $match = $this->router->match('GET', '/yonetim/ballots/5/candidates');
        $this->assertNotNull($match);
        $this->assertEquals('5', $match['params']['ballotId']);
    }

    public function test_root_route(): void
    {
        $this->router->get('/', 'ResultController', 'index');
        $match = $this->router->match('GET', '/');
        $this->assertNotNull($match);
        $this->assertEquals('ResultController', $match['controller']);
    }
}
