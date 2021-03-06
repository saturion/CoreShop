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

namespace CoreShop\Component\Order\Transformer;

use CoreShop\Component\Order\Model\ProposalInterface;

interface ProposalTransformerInterface
{
    /**
     * Transforms one proposal to another.
     *
     * @param ProposalInterface $fromProposal
     * @param ProposalInterface $toProposal
     *
     * @return mixed
     */
    public function transform(ProposalInterface $fromProposal, ProposalInterface $toProposal);
}
