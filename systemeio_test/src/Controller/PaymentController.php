<?php

namespace App\Controller;

use App\DTO\PaymentData;
use App\Entity\Coupons;
use App\Entity\Product;
use App\Entity\Tax;
use App\Exception\PriceCalculationException;
use App\Service\PaymentService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use http\Client\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PaymentController extends AbstractController
{
    private PaymentService $paymentService;
    private ValidatorInterface $validator;
    private CacheInterface $cache;

    public function __construct(
        PaymentService $paymentService,
        ValidatorInterface $validator,
        CacheInterface $cache
    ) {
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->cache = $cache;
    }


    /**
     * Обрабатывает запрос на расчет цены продукта с учетом налога и скидки.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/calculate-price', name: 'api_calculate_price', methods: ['POST'])]
    public function calculatePrice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new PaymentData(
            $data['product'] ?? null,
            $data['taxNumber'] ?? null,
            $data['couponCode'] ?? null
        );

        $validErrors = $this->validator->validate($dto, null, ['calculate-price']);

        if (count($validErrors) > 0) {
            return new JsonResponse(['error' => $this->formatValidationErrors($validErrors)], 422);
        }

        try {
            $cacheKey = 'calculate_price_' . md5(json_encode($data));

            $paymentData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($data) {
                $item->expiresAfter(3600);
                return $this->paymentService->calculatePrice($data);
            });

            return new JsonResponse($paymentData, 200);
        } catch (PriceCalculationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Обрабатывает запрос на покупку продукта, включая расчет цены и оплату.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/purchase', name: 'api_purchase', methods: ['POST'])]
    public function purchase(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new PaymentData(
            $data['product'] ?? null,
            $data['taxNumber'] ?? null,
            $data['couponCode'] ?? null,
            $data['paymentProcessor'] ?? null
        );

        $validErrors = $this->validator->validate($dto, null, ['purchase']);

        if (count($validErrors) > 0) {
            return new JsonResponse(['error' => $this->formatValidationErrors($validErrors)], 422);
        }

        try {
            $cacheKey = 'purchase_' . md5(json_encode($data));

            $paymentData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($data) {
                $item->expiresAfter(3600);
                return $this->paymentService->calculatePurchase($data);
            });

            return new JsonResponse($paymentData, 200);
        } catch (PriceCalculationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Форматирует ошибки валидации в массив строк.
     *
     * @param mixed $validErrors
     * @return array
     */
    private function formatValidationErrors($validErrors): array
    {
        $errors = [];
        foreach ($validErrors as $validError) {
            $errors[] = $validError->getMessage();
        }
        return $errors;
    }

    /**
     * Запрос для загрузки тестовых данных.
     */
    #[Route('/test-data-db', name: 'api_test_data_db', methods: ['POST'])]
    public function testDataDB(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $products = [
                ['Iphone', 100],
                ['Наушники', 20],
                ['Чехол', 10],
            ];

            foreach ($products as [$name, $price]) {
                $product = new Product();
                $product->setName($name);
                $product->setPrice($price);
                $entityManager->persist($product);
            }

            $coupons = [
                ['P1000', 1000, 'fixed'],
                ['S10', 10, 'percent'],
                ['S50', 50, 'percent'],
                ['P150', 150, 'fixed'],
            ];

            foreach ($coupons as [$name, $value, $type]) {
                $coupon = new Coupons();
                $coupon->setName($name);
                $coupon->setDiscountValue($value);
                $coupon->setDiscountType($type);
                $coupon->setIsActive(true);
                $entityManager->persist($coupon);
            }

            $taxes = [
                ['Germany', 19, 'DE', 'XXXXXXXXX'],
                ['Italy', 22, 'IT', 'XXXXXXXXXXX'],
                ['Greece', 20, 'GR', 'XXXXXXXXX'],
                ['France', 24, 'FR', 'YYXXXXXXXXX'],
            ];

            foreach ($taxes as [$country, $percent, $prefix, $pattern]) {
                $tax = new Tax();
                $tax->setCountry($country);
                $tax->setPercent($percent);
                $tax->setPrefix($prefix);
                $tax->setPattern($pattern);
                $entityManager->persist($tax);
            }

            $entityManager->flush();

            return new JsonResponse(['message' => 'Тестовые данные загружены в базу данных'], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
