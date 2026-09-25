<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Twig\Components\Demo\InspectorCart;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class InspectorDemoTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    public function testSearchReturnsOnlyMatchingProductsInsideTheTurboFrame(): void
    {
        $client = self::createClient();
        $client->request('GET', '/demos/inspector/results?q=LIVE');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, 'turbo-frame#results .result');
        self::assertSelectorExists('#product-1 a[data-turbo-stream][data-action="demo-product#remove"]');
        self::assertSelectorNotExists('#product-2');

        $client->request('GET', '/demos/inspector/results?q=missing');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, '.result');
        self::assertSelectorTextSame('turbo-frame#results', '');
    }

    public function testCartSurvivesLiveRequestsWithoutDuplicatesOrUnknownProducts(): void
    {
        $cart = $this->createLiveComponent('Demo:InspectorCart');
        $cart->call('add', ['product' => 1])->call('add', ['product' => 1])->call('add', ['product' => 99]);
        $component = $cart->component();
        self::assertInstanceOf(InspectorCart::class, $component);
        self::assertSame([1], $component->items);

        $cart->call('add', ['product' => 2]);
        self::assertCount(2, $cart->render()->crawler()->filter('.basket-item'));
        $cart->call('clear');
        self::assertCount(0, $cart->render()->crawler()->filter('.basket-item'));
    }

    public function testLiveSearchDispatchesTheQueryForTheTurboController(): void
    {
        $search = $this->createLiveComponent('Demo:InspectorSearch', ['query' => 'live']);
        $search->call('search');
        $this->assertComponentDispatchBrowserEvent($search, 'inspector:search')->withPayloadSubset(['query' => 'live']);
    }

    public function testEveryScenarioHasItsOwnPageWithoutReadableStorefrontText(): void
    {
        $client = self::createClient();
        foreach (['find', 'inspect', 'connect', 'trace'] as $page) {
            $client->request('GET', '/demos/inspector/'.$page);
            self::assertResponseIsSuccessful();
            self::assertSelectorExists('main[data-page="'.$page.'"]');
            if ('find' === $page) {
                self::assertSelectorNotExists('ux-inspector[open]');
                self::assertSelectorNotExists('html[data-ux-inspector-open]');
            } else {
                self::assertSelectorExists('ux-inspector[open]');
                self::assertSelectorExists('html[data-ux-inspector-open]');
            }
            self::assertSelectorTextSame('main.store', '');
            self::assertSelectorTextSame('.store-header', '');
            self::assertSelectorExists('turbo-frame#product-updates[hidden]');
            self::assertSelectorNotExists('.payment-placeholders');
            if ('inspect' === $page) {
                self::assertSelectorCount(6, '#results .result');
                self::assertSelectorNotExists('[data-demo-product-demo-cart-outlet]');
                self::assertSelectorNotExists('[data-controller="demo-cart-row"]');
            } else {
                self::assertSelectorCount(4, '#checkout-items .result');
                self::assertSelectorExists('[data-demo="cart"] [data-demo="total"][data-demo-total="120"]');
                self::assertSelectorExists('[data-demo="cart"] [data-demo="delivery"]');
                self::assertSelectorExists('[data-demo-cart-row-demo-cart-outlet]');
            }
        }
    }

    public function testProductRemovalRespondsWithRealTurboStreams(): void
    {
        $client = self::createClient();
        $client->request('GET', '/demos/inspector/remove/4');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');
        self::assertSelectorExists('turbo-stream[action="remove"][target="product-4"]');
        self::assertSelectorExists('turbo-stream[action="update"][target="product-updates"]');
    }

    public function testCartRemovalUpdatesTheSubtotal(): void
    {
        $cart = $this->createLiveComponent('Demo:InspectorCart', ['items' => [1, 2, 3, 4]]);
        $cart->call('remove', ['product' => 4]);
        self::assertSame([1, 2, 3], $cart->component()->items);
        self::assertSame(78, $cart->component()->getSubtotal());
        $cart->call('remove', ['product' => 4]);
        self::assertSame(78, $cart->component()->getSubtotal());
    }

    public function testDeliveryEventUpdatesTheNestedTotal(): void
    {
        $total = $this->createLiveComponent('Demo:InspectorTotal', ['subtotal' => 120]);
        $total->emit('inspector:delivery-changed', ['express' => true]);
        self::assertSame(132, $total->component()->getTotal());
        $total->emit('inspector:delivery-changed', ['express' => false]);
        self::assertSame(120, $total->component()->getTotal());
    }

    public function testFilterDispatchesItsQueryForTheResultsFrame(): void
    {
        $search = $this->createLiveComponent('Demo:InspectorSearch');
        $search->call('filter', ['query' => 'live']);
        self::assertSame('live', $search->component()->query);
        $this->assertComponentDispatchBrowserEvent($search, 'inspector:search')->withPayloadSubset(['query' => 'live']);
    }
}
