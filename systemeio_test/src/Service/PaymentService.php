<?php

namespace App\Service;

use App\Entity\Tax;
use App\Exception\PriceCalculationException;
use App\Repository\CouponsRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentService
{
    private ProductRepository $productRepository;
    private CouponsRepository $couponRepository;
    private TaxRepository $taxRepository;

    public function __construct(
        ProductRepository $productRepository,
        CouponsRepository $couponRepository,
        TaxRepository $taxRepository,
    ) {
        $this->productRepository = $productRepository;
        $this->couponRepository = $couponRepository;
        $this->taxRepository = $taxRepository;
    }

    /**
     * Применяет скидку к цене в зависимости от типа скидки.
     *
     * @param string $discountType
     * @param int $discountValue
     * @param float $price
     * @return int
     * @throws PriceCalculationException
     */
    public function coupon(string $discountType, int $discountValue, float $price): float
    {
        if ($discountType == 'fixed') {
            $price = ($price < $discountValue) ? 0 : $price - $discountValue;
        } else if ($discountType == 'percent') {
            $price -= ($price * $discountValue / 100);
        } else {
            throw new PriceCalculationException('Неверный тип скидки.');
        }
        return $price;
    }

    /**
     * Применяет налог к цене.
     *
     * @param float $price
     * @param int $percent
     * @return float
     */
    public function tax(float $price, int $percent): float
    {
        if (!empty($percent)) {
            $price += ($price * $percent / 100);
        }
        return $price;
    }

    /**
     * Проверяет налоговый номер и возвращает информацию о налоге.
     *
     * @param string $taxId
     * @param array $taxPatterns
     * @return Tax
     * @throws PriceCalculationException
     */
    public function validateTax(string $taxId, array $taxPatterns): Tax
    {
        $taxData = null;
        foreach ($taxPatterns as $pattern) {
            $prefix = $pattern->getPrefix();
            $format = $pattern->getPattern();

            if (strpos($taxId, $prefix) === 0) {
                $suffix = substr($taxId, strlen($prefix));

                $regex = '/^' . str_replace(['X', 'Y'], ['\d', '[A-Z]'], $format) . '$/';

                if (preg_match($regex, $suffix) === 1) {
                    $taxData = $pattern;
                }
            }
        }

        if (!$taxData) {
            throw new PriceCalculationException('Налоговый номер не соответствует ни одному шаблону.');
        }

        return $taxData;
    }

    /**
     * Обрабатывает оплату через PayPal или Stripe.
     *
     * @param string $paymentProcessor
     * @param float $price
     * @return bool
     * @throws PriceCalculationException
     */
    public function payProcessing(string $paymentProcessor, float $price): bool
    {
        if ($paymentProcessor == 'paypal') {
            $paypalProcessor = new PaypalPaymentProcessor();
            $paypalProcessor->pay($price);
            return true;
        } else if ($paymentProcessor == 'stripe') {
            $StripeProcessor = new StripePaymentProcessor();
            if ($StripeProcessor->processPayment($price)) {
                return true;
            } else {
                throw new PriceCalculationException('Ошибка при оплате с помощью stripe.');
            }
        } else {
            throw new PriceCalculationException('Ошибочный вид оплаты.');
        }
    }

    /**
     * Рассчитывает итоговую цену продукта с учетом скидки и налога.
     *
     * @param array $data
     * @return array
     * @throws PriceCalculationException
     */
    private function calculateProduct(array $data): array
    {
        $couponCode = $data['couponCode'] ?? null;

        $product = $this->productRepository->find($data['product']);
        if (!$product) {
            throw new PriceCalculationException('Продукт не найден.');
        }

        $price = $product->getPrice();
        $nameProduct = $product->getName();

        if (!empty($couponCode)) {
            $coupon = $this->couponRepository->findOneBy(['name' => $couponCode]);
            if (!$coupon) {
                throw new PriceCalculationException('Такого купона нету.');
            }

            $discountType = $coupon->getDiscountType();
            $discountValue = $coupon->getDiscountValue();
            $price = $this->coupon($discountType, $discountValue, $price);
        }

        $validateTax = $this->validateTax($data['taxNumber'], $this->taxRepository->findAll());

        if ($validateTax && !empty($validateTax->getPercent())) {
            $price = $this->tax($price, $validateTax->getPercent());
        } else {
            throw new PriceCalculationException('Налоговая информация не найдена.');
        }

        return [
            'nameProduct' => $nameProduct,
            'price' => $price,
            'validateTax' => $validateTax
        ];
    }

    /**
     * Возвращает информацию о продукте с учетом скидки и налога.
     *
     * @param array $data
     * @return array
     * @throws PriceCalculationException
     */
    public function calculatePrice(array $data): array
    {
        $paymentData = $this->calculateProduct($data);

        return [
            'name' => $paymentData['nameProduct'],
            'price' => $paymentData['price'],
            'tax' => $paymentData['validateTax']->getPercent()
        ];
    }

    /**
     * Обрабатывает покупку продукта, включая расчет цены и оплату.
     *
     * @param array $data
     * @return array
     * @throws PriceCalculationException
     */
    public function calculatePurchase(array $data): array
    {
        $paymentData = $this->calculateProduct($data);
        $price = $paymentData['price'];
        $this->payProcessing($data['paymentProcessor'], $price);

        return [
            'name' => $paymentData['nameProduct'],
            'price' => $price,
            'tax' => $paymentData['validateTax']->getPercent(),
            'pay' => 'Оплата прошла успешно'
        ];
    }
}