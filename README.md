
Checkout Payment SDK Bundle 
============




Installation
============

Applications that use Symfony 2.8 - 3.4
----------------------------------

Open a command console, enter your project directory and execute:



    $ composer require checkout/payment_sdk_bundle


----------------------------------------

Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~


Step 1: Download the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~~~
Step 2: Enable the Bundle
~~~~~~~~~~~~~~~~~~~~~~~~~

Then, enable the bundle by adding it to the list of registered bundles
in the ``app/AppKernel.php`` file of your project:

.. code-block:: php

    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...

               new Checkout\Bundle\PaymentBundle\CheckoutPaymentBundle(),
            );

            // ...
        }

        // ...
    }



**_Author_: Omair Afzal
_Organization_: Eureka Technology Studio**

