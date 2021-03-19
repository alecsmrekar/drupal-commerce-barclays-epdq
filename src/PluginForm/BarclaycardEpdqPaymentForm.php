<?php

namespace Drupal\barclaycard_epdq\PluginForm;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\barclaycard_epdq\BarclaycardEdpqTrait;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BarclaycardEpdqPaymentForm.
 *
 * @package Drupal\barclaycard_epdq\PluginForm
 */
class BarclaycardEpdqPaymentForm extends PaymentOffsiteForm implements ContainerInjectionInterface{

  use BarclaycardEdpqTrait;

  protected $config;

  /**
   * BarclaycardEpdqPaymentForm constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('commerce_payment.commerce_payment_gateway.barclays_epdq');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Generates a customers full name based on the Order object
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *
   * @return string
   */
  private function generateCustomerName(Order $order) {
    $billing_profile = $order->getBillingProfile();
    $billing_address = $billing_profile->address->first()->getValue();
    $name = $billing_address['given_name'];
    $middle = $billing_address['additional_name'];
    $surname = $billing_address['family_name'];
    if ($middle) {
      return sprintf('%s %s %s', $name, $middle, $surname);
    }
    return sprintf('%s %s', $name, $surname);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment = $this->entity;
    $amount = $payment->getAmount();
    $order = $payment->getOrder();
    $billing_address = $order->getBillingProfile()->address->first();
    $address = $billing_address->getAddressLine1();
    if ($billing_address->getAddressLine2()) {
      $address .= ', ' . $billing_address->getAddressLine2();
    }

    $name = $this->generateCustomerName($order);
    $amount_value = number_format($amount->getNumber()*100, 0, '.', '');
    $amount_currency = $amount->getCurrencyCode();

    $redirection_url = $this->config->get('configuration.redirect_url');
    $accept_url = $this->config->get('configuration.accept_url');
    $cancel_url = $this->config->get('configuration.cancel_url');

    $data = [
      'AMOUNT'       => $amount_value,
      'CN'           => $name,
      'OWNERADDRESS' => $address,
      'OWNERTOWN'    => $billing_address->getLocality(),
      'OWNERZIP'     => $billing_address->getPostalCode(),
      'OWNERCTY'     => $billing_address->getCountryCode(),
      'CURRENCY'     => $amount_currency,
      'EMAIL'        => $email = $order->getEmail(),
      'LANGUAGE'     => $this->config->get('configuration.locale'),
      'LOGO'         => $this->config->get('configuration.logo_url'),
      'ORDERID'      => $order->id(),
      'PSPID'        => $this->config->get('configuration.pspid'),
      'ACCEPTURL'    => $accept_url ? $accept_url : $form['#return_url'],
      'DECLINEURL'   => $this->config->get('configuration.decline_url'),
      'EXCEPTIONURL' => $this->config->get('configuration.exception_url'),
      'CANCELURL'    => $cancel_url ? $cancel_url : $form['#cancel_url'],
      'BACKURL'      => $this->config->get('configuration.back_url'),
      'HOMEURL'      => $this->config->get('configuration.home_url'),
      'TITLE'        => $order->getStore()->getName(),
    ];

    ksort($data);
    $sha_in_passphrase = $this->config->get('configuration.sha_in_passphrase');
    $sha_signature = $this->getShaSignature($data, (string) $sha_in_passphrase);
    $data['SHASIGN'] = $sha_signature;

    $redirect_form = $this->buildRedirectForm(
      $form,
      $form_state,
      $redirection_url,
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );
    return $redirect_form;
  }

}
