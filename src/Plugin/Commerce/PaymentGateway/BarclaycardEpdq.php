<?php

namespace Drupal\barclaycard_epdq\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\barclaycard_epdq\BarclaycardEdpqTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Barclaycard ePDQ offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "barclaycard_epdq",
 *   label = @Translation("Barclaycard ePDQ"),
 *   display_label = @Translation("Barclaycard ePDQ"),
 *   modes = {
 *     "live" = @Translation("Live"),
 *     "test" = @Translation("Test")
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\barclaycard_epdq\PluginForm\BarclaycardEpdqPaymentForm",
 *   },
 *   credit_card_types = {
 *     "mastercard", "visa", "maestro",
 *   },
 * )
 */
class BarclaycardEpdq extends OffsitePaymentGatewayBase {

  use BarclaycardEdpqTrait;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time')
    );
  }

  /**
   * Return the modes, test or live
   *
   * @return array
   */
  public function getSupportedModes() {
    return [
      'live' => $this->t('Live'),
      'test' => $this->t('Test')
      ];
  }

  /**
   *
   *
   * @param string $url
   *    An optional url based on which we can determine the mode
   *
   * @return string
   *    The mode
   */
  public function getMode($url = '') {
    if ($url == '') {
      $url = strtolower($this->configuration['redirect_url']);
    }
    if (strpos($url, '/test/') === FALSE) {
      return 'live';
    }
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'redirect_url'       => '',
      'pspid'              => '',
      'sha_in_passphrase'  => '',
      'sha_out_passphrase' => '',
      'accept_url'         => '',
      'decline_url'        => '',
      'exception_url'      => '',
      'cancel_url'         => '',
      'back_url'           => '',
      'home_url'           => '',
      'locale'             => '',
      'logo_url'           => '',
    ] + parent::defaultConfiguration();

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['redirect_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Redirect URL'),
      '#description'   => $this->t('This is the payment page the user will be redirected to.'),
      '#default_value' => $this->configuration['redirect_url'],
      '#required'      => TRUE,
    ];

    $form['pspid'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('PSPID'),
      '#description'   => $this->t('Your Barclaycard ePDQ account PSPID'),
      '#default_value' => $this->configuration['pspid'],
      '#required'      => TRUE,
    ];

    $form['sha_in_passphrase'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('SHA in passphrase'),
      '#description'   => $this->t('The SHA in passphrase for your Barclaycard ePDQ account.'),
      '#default_value' => $this->configuration['sha_in_passphrase'],
      '#required'      => TRUE,
    ];

    $form['sha_out_passphrase'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('SHA out passphrase'),
      '#description'   => $this->t('The SHA out passphrase for your Barclaycard ePDQ account.'),
      '#default_value' => $this->configuration['sha_out_passphrase'],
      '#required'      => TRUE,
    ];

    $form['accept_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Accept URL'),
      '#description'   => $this->t('The URL that users are redirected to after paying for their order.'),
      '#default_value' => $this->configuration['accept_url'],
      '#required'      => FALSE,
    ];

    $form['decline_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Decline URL'),
      '#description'   => $this->t('The URL users are redirected to if their payment has been declined the maximum allowed times.'),
      '#default_value' => $this->configuration['decline_url'],
      '#required'      => FALSE,
    ];

    $form['exception_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Exception URL'),
      '#description'   => $this->t('The URL users are redirected to if an error occurs when they are paying.'),
      '#default_value' => $this->configuration['exception_url'],
      '#required'      => FALSE,
    ];

    $form['cancel_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cancel URL'),
      '#description'   => $this->t('The URL users are redirected to if they click cancel on the payment page.'),
      '#default_value' => $this->configuration['cancel_url'],
      '#required'      => FALSE,
    ];

    $form['back_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Back URL'),
      '#description'   => $this->t('The back URL.'),
      '#default_value' => $this->configuration['back_url'],
      '#required'      => FALSE,
    ];

    $form['home_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Home URL'),
      '#description'   => $this->t('The home URL.'),
      '#default_value' => $this->configuration['home_url'],
      '#required'      => FALSE,
    ];

    $form['locale'] = [
      '#type'          => 'select',
      '#options'       => $this->getLocaleCodes(),
      '#multiple'      => FALSE,
      '#title'         => $this->t('Store Language Locale'),
      '#description'   => $this->t('The locale which is communicated to Barclays ePDQ.'),
      '#default_value' => $this->configuration['locale'] ? $this->configuration['locale'] : 'en_US',
      '#required'      => TRUE,
    ];

    $form['logo_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Logo URL'),
      '#description'   => $this->t('URL of the the store logo.'),
      '#default_value' => $this->configuration['logo_url'],
      '#required'      => FALSE,
    ];

    $parent = parent::buildConfigurationForm($form, $form_state);

    // Hide the mode input, this is automatically determined based on the redirect url
    if (key_exists('mode', $parent)) {
      $parent['mode']['#type'] = 'hidden';
    }
    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Use the modules custom logic to determine the mode
    $values = $form_state->getValue($form['#parents']);
    $url = $values['redirect_url'];
    $form_state->setValue('mode', $this->getMode($url));

    // Submit the form
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['redirect_url'] = $values['redirect_url'];
    $this->configuration['pspid'] = $values['pspid'];
    $this->configuration['sha_in_passphrase'] = $values['sha_in_passphrase'];
    $this->configuration['sha_out_passphrase'] = $values['sha_out_passphrase'];
    $this->configuration['accept_url'] = $values['accept_url'];
    $this->configuration['decline_url'] = $values['decline_url'];
    $this->configuration['exception_url'] = $values['exception_url'];
    $this->configuration['cancel_url'] = $values['cancel_url'];
    $this->configuration['back_url'] = $values['back_url'];
    $this->configuration['home_url'] = $values['home_url'];
    $this->configuration['locale'] = $values['locale'];
    $this->configuration['logo_url'] = $values['logo_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $order->id(),
      'remote_state' => 'accepted',
      'authorized' => $this->time->getRequestTime(),
    ]);
    $payment->save();
  }

  /**
   * @inheritDoc
   */
  public function onCancel(OrderInterface $order, Request $request) {

    // ePQD does not allow us to process the same order ID twice

    $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway. If you wish to retry, please re-add the products to your cart.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));

  }

}
