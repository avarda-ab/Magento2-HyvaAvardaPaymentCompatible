<?php

namespace Avarda\HyvaAvardaPaymentCompatible\Model\Magewire\Checkout\Payment\Method;

use Avarda\HyvaAvardaPaymentCompatible\ViewModel\HyvaCheckoutAvardaPayments;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;
use Rakit\Validation\Validator;

class AvardaPayments extends Form
{
    public ?string $socialSecurityNumber = null;

    protected $loader = [
        'saveSocialSecurityNumber' => 'Saving SSN',
    ];

    protected $rules = [
        'socialSecurityNumber' => 'required',
    ];

    protected $messages = [
        'socialSecurityNumber:required' => 'The SSN is a required field.',
    ];

    protected SessionCheckout $sessionCheckout;
    protected CartRepositoryInterface $quoteRepository;
    protected HyvaCheckoutAvardaPayments $hyvaCheckoutAvardaPayments;

    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        HyvaCheckoutAvardaPayments $hyvaCheckoutAvardaPayments
    ) {
        parent::__construct($validator);

        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
        $this->hyvaCheckoutAvardaPayments = $hyvaCheckoutAvardaPayments;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function mount(): void
    {
        $additionalData = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation('avarda_payments_ssn');
        $this->socialSecurityNumber = $additionalData ?? null;
    }

    public function saveSocialSecurityNumber(string $value): void
    {
        $this->socialSecurityNumber = $this->updatedSocialSecurityNumber(trim($value));
    }

    public function updatedSocialSecurityNumber(string $value): ?string
    {
        $value = empty($value) ? null : $value;

        try {
            $quote = $this->sessionCheckout->getQuote();
            $quote->getPayment()->setAdditionalInformation('avarda_payments_ssn', $value);

            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }

        return $value;
    }

    public function getTermsHtml(string $method): string
    {
        return $this->hyvaCheckoutAvardaPayments->getTermsHtml($method);
    }
}
