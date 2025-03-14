<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\Coupons;
use App\Entity\Tax;
use App\Repository\CouponsRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Тестирует эндпоинт /calculate-price с корректными данными.
     */
    public function testCalculatePriceSuccess()
    {
        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $couponRepositoryMock = $this->createMock(CouponsRepository::class);
        $taxRepositoryMock = $this->createMock(TaxRepository::class);

        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100.0);

        $coupon = new Coupons();
        $coupon->setName('S15');
        $coupon->setDiscountType('percent');
        $coupon->setDiscountValue(15);

        $tax = new Tax();
        $tax->setPrefix('DE');
        $tax->setPattern('XXXXXXXXX');
        $tax->setPercent(19);

        $productRepositoryMock->method('find')
            ->willReturn($product);

        $couponRepositoryMock->method('findOneBy')
            ->willReturn($coupon);

        $taxRepositoryMock->method('findAll')
            ->willReturn([$tax]);

        self::getContainer()->set(ProductRepository::class, $productRepositoryMock);
        self::getContainer()->set(CouponsRepository::class, $couponRepositoryMock);
        self::getContainer()->set(TaxRepository::class, $taxRepositoryMock);

        $this->client->request(
            'POST',
            '/calculate-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'product' => 1,
                'taxNumber' => 'DE123456789',
                'couponCode' => 'D15',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('price', $responseData);
        $this->assertArrayHasKey('tax', $responseData);
    }

    /**
     * Тестирует эндпоинт /purchase с корректными данными.
     */
    public function testPurchaseSuccess()
    {
        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $couponRepositoryMock = $this->createMock(CouponsRepository::class);
        $taxRepositoryMock = $this->createMock(TaxRepository::class);

        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100.0);

        $coupon = new Coupons();
        $coupon->setName('S15');
        $coupon->setDiscountType('percent');
        $coupon->setDiscountValue(15);

        $tax = new Tax();
        $tax->setPrefix('IT');
        $tax->setPattern('XXXXXXXXXXX');
        $tax->setPercent(22);

        $productRepositoryMock->method('find')
            ->willReturn($product);

        $couponRepositoryMock->method('findOneBy')
            ->willReturn($coupon);

        $taxRepositoryMock->method('findAll')
            ->willReturn([$tax]);

        self::getContainer()->set(ProductRepository::class, $productRepositoryMock);
        self::getContainer()->set(CouponsRepository::class, $couponRepositoryMock);
        self::getContainer()->set(TaxRepository::class, $taxRepositoryMock);

        $paypalProcessorMock = $this->createMock(PaypalPaymentProcessor::class);
        $paypalProcessorMock->method('pay')
            ->willReturnCallback(function () {});

        self::getContainer()->set(PaypalPaymentProcessor::class, $paypalProcessorMock);

        $this->client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'product' => 1,
                'taxNumber' => 'IT12345678900',
                'couponCode' => 'D15',
                'paymentProcessor' => 'paypal',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('price', $responseData);
        $this->assertArrayHasKey('tax', $responseData);
        $this->assertArrayHasKey('pay', $responseData);
    }

    /**
     * Тестирует эндпоинт /purchase с ошибкой оплаты.
     */
    public function testPurchasePaymentError()
    {
        // Замокаем репозитории
        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $couponRepositoryMock = $this->createMock(CouponsRepository::class);
        $taxRepositoryMock = $this->createMock(TaxRepository::class);

        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100.0);

        $coupon = new Coupons();
        $coupon->setName('S15');
        $coupon->setDiscountType('percent');
        $coupon->setDiscountValue(15);

        $tax = new Tax();
        $tax->setPrefix('IT');
        $tax->setPattern('XXXXXXXXXXX');
        $tax->setPercent(22);

        $productRepositoryMock->method('find')
            ->willReturn($product);

        $couponRepositoryMock->method('findOneBy')
            ->willReturn($coupon);

        $taxRepositoryMock->method('findAll')
            ->willReturn([$tax]);

        self::getContainer()->set(ProductRepository::class, $productRepositoryMock);
        self::getContainer()->set(CouponsRepository::class, $couponRepositoryMock);
        self::getContainer()->set(TaxRepository::class, $taxRepositoryMock);

        $stripeProcessorMock = $this->createMock(StripePaymentProcessor::class);
        $stripeProcessorMock->method('processPayment')
            ->willReturn(false);

        self::getContainer()->set(StripePaymentProcessor::class, $stripeProcessorMock);

        $this->client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'product' => 1,
                'taxNumber' => 'IT1234567+8900',
                'couponCode' => 'S15',
                'paymentProcessor' => 'stripe',
            ])
        );

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
    }
}