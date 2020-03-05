<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\CoreBundle\Templating\Helper;

use CoreShop\Component\Order\Checkout\CheckoutManagerFactoryInterface;
use CoreShop\Component\Order\Checkout\CheckoutManagerInterface;
use CoreShop\Component\Order\Checkout\CheckoutStepInterface;
use CoreShop\Component\Order\Checkout\ValidationCheckoutStepInterface;
use CoreShop\Component\Order\Context\CartContextInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Pimcore\Routing\LinkGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\Helper\Helper;

class CheckoutIdentifierHelper extends Helper implements CheckoutIdentifierHelperInterface
{
    private $requestStack;
    protected $linkGenerator;
    protected $checkoutManagerFactory;
    protected $cartContext;

    public function __construct(
        RequestStack $requestStack,
        LinkGeneratorInterface $linkGenerator,
        CheckoutManagerFactoryInterface $checkoutManagerFactory,
        CartContextInterface $cartContext
    ) {
        $this->requestStack = $requestStack;
        $this->linkGenerator = $linkGenerator;
        $this->checkoutManagerFactory = $checkoutManagerFactory;
        $this->cartContext = $cartContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getSteps(): array
    {
        $cart = $this->cartContext->getCart();
        $request = $this->requestStack->getMasterRequest();
        $stepIdentifier = $request->get('stepIdentifier');
        $requestAttributes = $request->attributes;
        $checkoutManager = $this->checkoutManagerFactory->createCheckoutManager($cart);
        $currentStep = 0;

        if ($stepIdentifier) {
            $currentStep = $checkoutManager->getCurrentStepIndex($stepIdentifier);
        }

        $checkoutSteps = $checkoutManager->getSteps();

        //always add cart to checkout
        $shopSteps = [
            'cart' => [
                'waiting' => false,
                'done' => null !== $stepIdentifier,
                'current' => $requestAttributes->get('_route') === 'coreshop_cart_summary',
                'valid' => null !== $stepIdentifier,
                'url' => $this->linkGenerator->generate($cart, 'coreshop_cart_summary'),
            ],
        ];

        foreach ($checkoutSteps as $identifier) {
            $stepIndex = $checkoutManager->getCurrentStepIndex($identifier);
            $step = $checkoutManager->getStep($identifier);
            $isValid = $step instanceof ValidationCheckoutStepInterface ? $step->validate($cart) : false;

            $shopSteps[$identifier] = [
                'waiting' => null === $stepIdentifier || $currentStep < $stepIndex,
                'done' => null !== $stepIdentifier && $currentStep > $stepIndex,
                'current' => null !== $stepIdentifier && $currentStep === $stepIndex,
                'valid' => $isValid,
                'url' => $this->linkGenerator->generate($cart, 'coreshop_checkout', ['stepIdentifier' => $identifier]),
            ];
        }

        return $shopSteps;
    }

    /**
     * {@inheritdoc}
     */
    public function getStep(string $type = '')
    {
        $validGuesser = ['get_first', 'get_previous', 'get_current', 'get_next', 'get_last'];

        $getter = lcfirst(str_replace('_', '', ucwords($type, '_'))) . 'StepIdentifier';
        if (!method_exists($this, $getter)) {
            throw new \InvalidArgumentException(sprintf('invalid identifier guess "%s", available guesses are: %s', $type, implode(', ', $validGuesser)));
        }

        $cart = $this->cartContext->getCart();
        $checkoutManager = $this->checkoutManagerFactory->createCheckoutManager($cart);
        $request = $this->requestStack->getMasterRequest();
        $stepIdentifier = $request->get('stepIdentifier');

        return $this->$getter($cart, $stepIdentifier, $checkoutManager);
    }

    protected function getPreviousStepIdentifier(OrderInterface $cart, $stepIdentifier, CheckoutManagerInterface $checkoutManager)
    {
        $identifier = null;
        $request = $this->requestStack->getMasterRequest();
        $previousIdentifier = $request->get('stepIdentifier');

        if (null !== $previousIdentifier) {
            if ($checkoutManager->hasPreviousStep($previousIdentifier)) {
                $step = $checkoutManager->getPreviousStep($previousIdentifier);
                return $step->getIdentifier();
            }
        }

        return null;
    }


    protected function getCurrentStepIdentifier(OrderInterface $cart, $stepIdentifier, CheckoutManagerInterface $checkoutManager)
    {
        return $stepIdentifier;
    }

    protected function getFirstStepIdentifier(OrderInterface $cart, $stepIdentifier, CheckoutManagerInterface $checkoutManager)
    {
        $steps = $checkoutManager->getSteps();

        return reset($steps);
    }

    /**
     * @param OrderInterface            $cart
     * @param string                   $stepIdentifier
     * @param CheckoutManagerInterface $checkoutManager
     *
     * @return mixed
     */
    protected function getLastStepIdentifier($cart, $stepIdentifier, $checkoutManager)
    {
        $steps = $checkoutManager->getSteps();

        return end($steps);
    }

    /**
     * @param OrderInterface            $cart
     * @param string                   $stepIdentifier
     * @param CheckoutManagerInterface $checkoutManager
     *
     * @return mixed
     */
    protected function getNextStepIdentifier(OrderInterface $cart, string $stepIdentifier, CheckoutManagerInterface $checkoutManager)
    {
        $identifier = null;

        if (null !== $stepIdentifier) {

            if ($checkoutManager->hasNextStep($stepIdentifier)) {
                $step = $checkoutManager->getNextStep($stepIdentifier);
                $identifier = $step->getIdentifier();
            }
        }

        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'coreshop_checkout_identifier';
    }
}
