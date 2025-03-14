<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentData
{
    #[Assert\NotBlank(message: 'Требуется идентификатор продукта.', groups: ['calculate-price', 'purchase'])]
    #[Assert\Type(type: 'integer', message: 'Идентификатор продукта должен быть целым числом.')]
    public ?int $product = null;

    #[Assert\NotBlank(message: 'Требуется налоговый номер.', groups: ['calculate-price', 'purchase'])]
    #[Assert\Type(type: 'string', message: 'Налоговый номер должен быть строкой.')]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9]+$/', message: 'Налоговый номер должен содержать только буквы и цифры.')]

    public ?string $taxNumber = null;

    #[Assert\Type(type: 'string', message: 'Код купона должен быть строкой.')]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9]+$/', message: 'Код купона должен содержать только буквы и цифры.')]
    public ?string $couponCode = null;

    #[Assert\NotBlank(message: 'Для совершения покупки требуется платежный процессор.', groups: ['purchase'])]
    #[Assert\Choice(choices: ['paypal', 'stripe'], message: 'Недействительный платежный процессор.', groups: ['purchase'])]
    public ?string $paymentProcessor = null;

    public function __construct(?int $product = null, ?string $taxNumber = null, ?string $couponCode = null, ?string $paymentProcessor = null)
    {
        $this->product = $product;
        $this->taxNumber = $taxNumber;
        $this->couponCode = $couponCode;
        $this->paymentProcessor = $paymentProcessor;
    }
}
