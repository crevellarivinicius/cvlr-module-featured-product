<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Crevellari_FeaturedProduct',
    __DIR__
);
