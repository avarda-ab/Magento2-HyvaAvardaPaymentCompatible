<?php

namespace Avarda\HyvaAvardaPaymentCompatible\ViewModel;

use Avarda\Payments\Model\GetAprWidgetHtml;
use Avarda\Payments\Model\Ui\Invoice\ConfigProvider as InvoiceConfigProvider;
use Avarda\Payments\Model\Ui\DirectInvoice\ConfigProvider as DirectInvoiceConfigProvider;
use Avarda\Payments\Model\Ui\Loan\ConfigProvider as LoanConfigProvider;
use Avarda\Payments\Model\Ui\PartPayment\ConfigProvider as PartPaymentConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class HyvaCheckoutAvardaPayments implements ArgumentInterface
{
    protected ConfigProviderInterface $invoice;
    protected ConfigProviderInterface $directInvoice;
    protected ConfigProviderInterface $loan;
    protected ConfigProviderInterface $partPayment;
    protected GetAprWidgetHtml $getAprWidgetHtml;
    protected Escaper $escaper;

    public function __construct(
        ConfigProviderInterface $avardaInvoiceProvider,
        ConfigProviderInterface $avardaDirectInvoiceProvider,
        ConfigProviderInterface $avardaLoanProvider,
        ConfigProviderInterface $avardaPartPaymentProvider,
        GetAprWidgetHtml $getAprWidgetHtml,
        Escaper $escaper
    ) {
        $this->invoice = $avardaInvoiceProvider;
        $this->directInvoice = $avardaDirectInvoiceProvider;
        $this->loan = $avardaLoanProvider;
        $this->partPayment = $avardaPartPaymentProvider;
        $this->getAprWidgetHtml = $getAprWidgetHtml;
        $this->escaper = $escaper;
    }

    public function getTermsHtml(string $method): string
    {
        $instructor = $this->getInstructor($method);
        if (!$instructor) {
            return '';
        }
        $config = $this->$instructor->getConfig()['payment']['instructions'][$method] ?? [];
        if ($config['apr_widget']['enabled']) {
            // Magewire evals re-rendered <script> tags, which strict CSP blocks; apr-widget-loader.phtml loads it.
            $html = $this->getAprWidgetHtml->execute($method);
            $html .= '<span hidden data-avarda-apr-widget' .
                ' data-src="' . $this->escaper->escapeHtmlAttr($config['apr_widget']['url']) . '"' .
                ' data-payment-id="' . $this->escaper->escapeHtmlAttr($config['apr_widget']['paymentId']) . '"' .
                ' data-widget-jwt="' . $this->escaper->escapeHtmlAttr($config['apr_widget']['widgetJwt']) . '"' .
                ' data-custom-styles="' . $this->escaper->escapeHtmlAttr($config['apr_widget']['styles']) . '"' .
                '></span>';
        } else {
            $html = '<span class="instructions">' . $config['instructions'] . '</span><br><br>';
            $html .= '<span class="text-xs">' .
                    $config['terms_text'] .
                    ' <a href="' . $config['terms_link'] . '" target="_blank" rel="noopener">(' . __('Terms') . ')</a>' .
                '</span>';
        }

        if (isset($config['show_loan_warning']) && $config['show_loan_warning']) {
            $html .= $this->$instructor->getLoanWarningHtml();
        }

        return $html;
    }

    public function getInstructor(string $method): string
    {
        return match ($method) {
            InvoiceConfigProvider::CODE => 'invoice',
            DirectInvoiceConfigProvider::CODE => 'directInvoice',
            LoanConfigProvider::CODE => 'loan',
            PartPaymentConfigProvider::CODE => 'partPayment',
            default => '',
        };
    }
}
