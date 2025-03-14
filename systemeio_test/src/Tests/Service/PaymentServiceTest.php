<?php

namespace App\Tests\Service;

use App\Entity\Coupons;
use App\Entity\Product;
use App\Entity\Tax;
use App\Exception\PriceCalculationException;
use App\Repository\CouponsRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use App\Service\PaymentService;
use Mockery;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentServiceTest extends TestCase
{
    private PaymentService $paymentService;
    private ProductRepository $productRepository;
    private CouponsRepository $couponRepository;
    private TaxRepository $taxRepository;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->couponRepository = $this->createMock(CouponsRepository::class);
        $this->taxRepository = $this->createMock(TaxRepository::class);

        $this->paymentService = new PaymentService(
            $this->productRepository,
            $this->couponRepository,
            $this->taxRepository
        );
    }

    /**
     * Тестирует метод calculatePrice с корректными данными.
     */
    public function testCalculatePriceSuccess(): void
    {
        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100);

        $coupon = new Coupons();
        $coupon->setName('D15');
        $coupon->setDiscountType('percent');
        $coupon->setDiscountValue(15);

        $tax = new Tax();
        $tax->setPrefix('DE');
        $tax->setPattern('XXXXXXXXX');
        $tax->setPercent(19);

        $this->productRepository->method('find')
            ->willReturn($product);

        $this->couponRepository->method('findOneBy')
            ->willReturn($coupon);

        $this->taxRepository->method('findAll')
            ->willReturn([$tax]);

        $result = $this->paymentService->calculatePrice([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'D15',
        ]);

        // Проверяем результат
        $this->assertEquals('Iphone', $result['name']);
        $this->assertEquals(101.15, $result['price']); // Исправлено на 101.15
        $this->assertEquals(19, $result['tax']);
    }

    /**
     * Тестирует метод calculatePrice с неверным налоговым номером.
     */
    public function testCalculatePriceInvalidTaxNumber(): void
    {
        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100);

        $this->productRepository->method('find')
            ->willReturn($product);

        $this->taxRepository->method('findAll')
            ->willReturn([]);

        $this->expectException(PriceCalculationException::class);
        $this->expectExceptionMessage('Налоговый номер не соответствует ни одному шаблону.');

        $this->paymentService->calculatePrice([
            'product' => 1,
            'taxNumber' => 'IT12345678900+-',
        ]);
    }

    /**
     * Тестирует метод calculatePurchase с успешной оплатой через PayPal.
     */
    public function testCalculatePurchasePaypalSuccess(): void
    {
        // Настраиваем моки
        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(100);

        $tax = new Tax();
        $tax->setPrefix('DE');
        $tax->setPattern('XXXXXXXXX');
        $tax->setPercent(19);

        $this->productRepository->method('find')
            ->willReturn($product);

        $this->taxRepository->method('findAll')
            ->willReturn([$tax]);

        $result = $this->paymentService->calculatePurchase([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => 'paypal',
        ]);

        // Проверяем результат
        $this->assertEquals('Iphone', $result['name']);
        $this->assertEquals(119, $result['price']);
        $this->assertEquals(19, $result['tax']);
        $this->assertEquals('Оплата прошла успешно', $result['pay']);
    }

    /**
     * Тестирует метод calculatePurchase с ошибкой оплаты через Stripe.
     */
    public function testCalculatePurchaseStripeFailure(): void
    {
        $product = new Product();
        $product->setName('Iphone');
        $product->setPrice(50);

        $tax = new Tax();
        $tax->setPrefix('DE');
        $tax->setPattern('XXXXXXXXX');
        $tax->setPercent(19);

        $this->productRepository->method('find')
            ->willReturn($product);

        $this->taxRepository->method('findAll')
            ->willReturn([$tax]);

        if (!class_exists(StripePaymentProcessor::class, false)) {
            $stripeProcessorMock = \Mockery::mock('overload:' . StripePaymentProcessor::class);
            $stripeProcessorMock->shouldReceive('processPayment')
                ->andReturn(false);
        } else {
            $stripeProcessorMock = \Mockery::instanceMock(StripePaymentProcessor::class);
            $stripeProcessorMock->shouldReceive('processPayment')
                ->andReturn(false);
        }

        $this->expectException(PriceCalculationException::class);
        $this->expectExceptionMessage('Ошибка при оплате с помощью stripe.');


        $this->paymentService->calculatePurchase([
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => 'stripe',
        ]);

        Mockery::close();
    }

    /**
     * Тестирует метод coupon с фиксированной скидкой.
     */
    public function testCouponFixedDiscount(): void
    {
        $result = $this->paymentService->coupon('fixed', 20, 100);

        $this->assertEquals(80, $result);
    }

    /**
     * Тестирует метод coupon с процентной скидкой.
     */
    public function testCouponPercentDiscount(): void
    {
        $result = $this->paymentService->coupon('percent', 15, 100);

        $this->assertEquals(85, $result);
    }

    /**
     * Тестирует метод coupon с неверным типом скидки.
     */
    public function testCouponInvalidDiscountType(): void
    {
        $this->expectException(PriceCalculationException::class);
        $this->expectExceptionMessage('Неверный тип скидки.');

        $this->paymentService->coupon('percent-moneyZ', 15, 100);
    }

    /**
     * Тестирует метод tax с налогом.
     */
    public function testTaxWithPercent(): void
    {
        $result = $this->paymentService->tax(100, 19);

        $this->assertEquals(119, $result);
    }

    /**
     * Тестирует метод tax без налога.
     */
    public function testTaxWithoutPercent(): void
    {
        $result = $this->paymentService->tax(100, 0);

        $this->assertEquals(100, $result);
    }
}