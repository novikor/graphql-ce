<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote\Guest;

use Exception;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for setting shipping methods on cart for guest
 */
class SetShippingMethodsOnCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     */
    public function testSetShippingMethodOnCartWithSimpleProduct()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $carrierCode = 'flatrate';
        $methodCode = 'flatrate';

        $query = $this->getQuery(
            $maskedQuoteId,
            $methodCode,
            $carrierCode
        );
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey('setShippingMethodsOnCart', $response);
        self::assertArrayHasKey('cart', $response['setShippingMethodsOnCart']);
        self::assertArrayHasKey('shipping_addresses', $response['setShippingMethodsOnCart']['cart']);
        self::assertCount(1, $response['setShippingMethodsOnCart']['cart']['shipping_addresses']);

        $shippingAddress = current($response['setShippingMethodsOnCart']['cart']['shipping_addresses']);
        self::assertArrayHasKey('selected_shipping_method', $shippingAddress);

        self::assertArrayHasKey('carrier_code', $shippingAddress['selected_shipping_method']);
        self::assertEquals('flatrate', $shippingAddress['selected_shipping_method']['carrier_code']);

        self::assertArrayHasKey('method_code', $shippingAddress['selected_shipping_method']);
        self::assertEquals('flatrate', $shippingAddress['selected_shipping_method']['method_code']);

        self::assertArrayHasKey('carrier_title', $shippingAddress['selected_shipping_method']);
        self::assertEquals('Flat Rate', $shippingAddress['selected_shipping_method']['carrier_title']);

        self::assertArrayHasKey('method_title', $shippingAddress['selected_shipping_method']);
        self::assertEquals('Fixed', $shippingAddress['selected_shipping_method']['method_title']);

        self::assertArrayHasKey('amount', $shippingAddress['selected_shipping_method']);
        $amount = $shippingAddress['selected_shipping_method']['amount'];

        self::assertArrayHasKey('value', $amount);
        self::assertEquals(10, $amount['value']);
        self::assertArrayHasKey('currency', $amount);
        self::assertEquals('USD', $amount['currency']);

        self::assertArrayHasKey('base_amount', $shippingAddress['selected_shipping_method']);
        $baseAmount = $shippingAddress['selected_shipping_method']['base_amount'];

        self::assertArrayHasKey('value', $baseAmount);
        self::assertEquals(10, $baseAmount['value']);
        self::assertArrayHasKey('currency', $baseAmount);
        self::assertEquals('USD', $baseAmount['currency']);
    }

    /**
     * Shipping address for quote will be created automatically BUT with NULL values (considered that address
     * is not set)
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     *
     * @expectedException Exception
     * @expectedExceptionMessage The shipping address is missing. Set the address and try again.
     */
    public function testSetShippingMethodOnCartWithSimpleProductAndWithoutAddress()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $carrierCode = 'flatrate';
        $methodCode = 'flatrate';

        $query = $this->getQuery(
            $maskedQuoteId,
            $methodCode,
            $carrierCode
        );
        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/enable_offline_shipping_methods.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     */
    public function testReSetShippingMethod()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $carrierCode = 'freeshipping';
        $methodCode = 'freeshipping';

        $query = $this->getQuery(
            $maskedQuoteId,
            $methodCode,
            $carrierCode
        );
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey('setShippingMethodsOnCart', $response);
        self::assertArrayHasKey('cart', $response['setShippingMethodsOnCart']);
        self::assertArrayHasKey('shipping_addresses', $response['setShippingMethodsOnCart']['cart']);
        self::assertCount(1, $response['setShippingMethodsOnCart']['cart']['shipping_addresses']);

        $shippingAddress = current($response['setShippingMethodsOnCart']['cart']['shipping_addresses']);
        self::assertArrayHasKey('selected_shipping_method', $shippingAddress);

        self::assertArrayHasKey('carrier_code', $shippingAddress['selected_shipping_method']);
        self::assertEquals($carrierCode, $shippingAddress['selected_shipping_method']['carrier_code']);

        self::assertArrayHasKey('method_code', $shippingAddress['selected_shipping_method']);
        self::assertEquals($methodCode, $shippingAddress['selected_shipping_method']['method_code']);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     *
     * @param string $input
     * @param string $message
     * @dataProvider dataProviderSetShippingMethodWithWrongParameters
     * @throws Exception
     */
    public function testSetShippingMethodWithWrongParameters(string $input, string $message)
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $input = str_replace('cart_id_value', $maskedQuoteId, $input);

        $query = <<<QUERY
mutation {
  setShippingMethodsOnCart(input:  {
   {$input}     
  }) {
    cart {
      shipping_addresses {
        selected_shipping_method {
          carrier_code
        }
      }
    }
  }
}
QUERY;
        $this->expectExceptionMessage($message);
        $this->graphQlMutation($query);
    }

    /**
     * Test region code returns as expected following a failure to set shipping methods
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @throws Exception
     */
    public function testShippingRegionOnMethodSetError()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $setAddressesResult = $this->graphQlMutation(
            $this->getSetShippingAddressWithLowerCaseCountryOnCartMutation($maskedQuoteId)
        );
        $setAddresses = $setAddressesResult['setShippingAddressesOnCart']['cart']['shipping_addresses'];

        $this->expectException(\Exception::class);
        try {
            $this->graphQlMutation($this->getInvalidSetShippingMethodMutation($maskedQuoteId));
        } catch (\Exception $e) {
            $currentShippingAddresses = $this->queryShippingAddresses($maskedQuoteId);
            $this->assertEquals($setAddresses[0]['region']['code'], $currentShippingAddresses[0]['region']['code']);
            throw $e;
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderSetShippingMethodWithWrongParameters(): array
    {
        return [
            'missed_cart_id' => [
                'shipping_methods: [{
                    carrier_code: "flatrate"
                    method_code: "flatrate"
                }]',
                'Required parameter "cart_id" is missing'
            ],
            'missed_shipping_methods' => [
                'cart_id: "cart_id_value"',
                'Required parameter "shipping_methods" is missing'
            ],
            'shipping_methods_are_empty' => [
                'cart_id: "cart_id_value" shipping_methods: []',
                'Required parameter "shipping_methods" is missing'
            ],
            'missed_carrier_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    method_code: "flatrate"
                }]',
                'Field ShippingMethodInput.carrier_code of required type String! was not provided.'
            ],
            'empty_carrier_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: ""
                    method_code: "flatrate"
                }]',
                'Required parameter "carrier_code" is missing.'
            ],
            'non_existent_carrier_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: "wrong-carrier-code"
                    method_code: "flatrate"
                }]',
                'Carrier with such method not found: wrong-carrier-code, flatrate'
            ],
            'missed_method_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: "flatrate"
                }]',
                'Required parameter "method_code" is missing.'
            ],
            'empty_method_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: "flatrate"
                    method_code: ""
                }]',
                'Required parameter "method_code" is missing.'
            ],
            'non_existent_method_code' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: "flatrate"
                    method_code: "wrong-carrier-code"
                }]',
                'Carrier with such method not found: flatrate, wrong-carrier-code'
            ],
            'non_existent_shopping_cart' => [
                'cart_id: "non_existent_masked_id", shipping_methods: [{
                    carrier_code: "flatrate"
                    method_code: "flatrate"
                }]',
                'Could not find a cart with ID "non_existent_masked_id"'
            ],
            'disabled_shipping_method' => [
                'cart_id: "cart_id_value", shipping_methods: [{
                    carrier_code: "freeshipping"
                    method_code: "freeshipping"
                }]',
                'Carrier with such method not found: freeshipping, freeshipping'
            ],
        ];
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/enable_offline_shipping_methods.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @expectedException Exception
     * @expectedExceptionMessage You cannot specify multiple shipping methods.
     */
    public function testSetMultipleShippingMethods()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $query = <<<QUERY
mutation {
  setShippingMethodsOnCart(input:  {
   cart_id: "{$maskedQuoteId}", 
   shipping_methods: [
        {
            carrier_code: "flatrate"
            method_code: "flatrate"
        }
        {
            carrier_code: "flatrate"
            method_code: "flatrate"
        }
   ]
  }) {
    cart {
      shipping_addresses {
        selected_shipping_method {
          carrier_code
        }
      }
    }
  }
}
QUERY;
        $this->graphQlMutation($query);
    }

    /**
     * _security
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     *
     * @expectedException Exception
     */
    public function testSetShippingMethodToCustomerCart()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $carrierCode = 'flatrate';
        $methodCode = 'flatrate';
        $query = $this->getQuery(
            $maskedQuoteId,
            $methodCode,
            $carrierCode
        );

        $this->expectExceptionMessage(
            "The current user cannot perform operations on cart \"$maskedQuoteId\""
        );
        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/quote_with_address.php
     *
     * @expectedException Exception
     * @expectedExceptionMessage The shipping method can't be set for an empty cart. Add an item to cart and try again.
     */
    public function testSetShippingMethodOnAnEmptyCart()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $carrierCode = 'flatrate';
        $methodCode = 'flatrate';

        $query = $this->getQuery(
            $maskedQuoteId,
            $methodCode,
            $carrierCode
        );
        $this->graphQlMutation($query);
    }

    /**
     * @param string $maskedQuoteId
     * @param string $shippingMethodCode
     * @param string $shippingCarrierCode
     * @return string
     */
    private function getQuery(
        string $maskedQuoteId,
        string $shippingMethodCode,
        string $shippingCarrierCode
    ): string {
        return <<<QUERY
mutation {
  setShippingMethodsOnCart(input: 
    {
      cart_id: "$maskedQuoteId", 
      shipping_methods: [{
        carrier_code: "$shippingCarrierCode"
        method_code: "$shippingMethodCode"
      }]
    }) {
    cart {
      shipping_addresses {
        selected_shipping_method {
          carrier_code
          method_code
          carrier_title
          method_title
          amount {
            value
            currency
          }
          base_amount {
            value
            currency
          }
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get mutation setting shipping address on cart with lowercase country code
     *
     * @param string $maskedQuoteId
     * @return string
     */
    private function getSetShippingAddressWithLowerCaseCountryOnCartMutation(string $maskedQuoteId): string
    {
        return <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "{$maskedQuoteId}"
      shipping_addresses: [
        {
          address: {
            firstname: "John"
            lastname: "Doe"
            street: ["6161 West Centinella Avenue"]
            city: "Culver City"
            region: "CA"
            postcode: "90230"
            country_code: "us"
            telephone: "555-555-55-55"
            save_in_address_book: false
          }
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        city
        postcode
        region {
          label
          code
        }
        selected_shipping_method {
          carrier_code
          method_code
        }
        available_shipping_methods {
          carrier_code
          method_code
          carrier_title
          method_title
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get mutation setting invalid shipping method on cart
     *
     * @param string $maskedQuoteId
     * @return string
     */
    private function getInvalidSetShippingMethodMutation(string $maskedQuoteId): string
    {
        return <<<QUERY
mutation {
  setShippingMethodsOnCart(input:  {
    cart_id: "{$maskedQuoteId}",
    shipping_methods: [{
      carrier_code: "flatrate"
      method_code: "wrong-carrier-code"
    }]
  }) {
    cart {
      shipping_addresses {
        selected_shipping_method {
          carrier_code
        }
      }
    }
  }
}
QUERY;
    }

    /**
     * Get current shipping addresses for a given masked quote id
     *
     * @param string $maskedQuoteId
     * @return array
     * @throws Exception
     */
    private function queryShippingAddresses(string $maskedQuoteId): array
    {
        $query = <<<QUERY
{
  cart(cart_id:"{$maskedQuoteId}") {
      shipping_addresses {
        street
        city
        postcode
        region {
          label
          code
        }
        country {
          code
          label
        }
      }
    }
}
QUERY;

        $result = $this->graphQlQuery($query);
        return $result['cart']['shipping_addresses'] ?? [];
    }
}
