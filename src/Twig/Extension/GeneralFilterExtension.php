<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace App\Twig\Extension;

use Pimcore\Translation\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GeneralFilterExtension extends AbstractExtension
{
    /**
     * GeneralFilterExtension constructor.
     */
    public function __construct(protected Translator $translator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_general_filter_translate', [$this, 'translateValues']),
            new TwigFunction('app_general_filter_sort', [$this, 'sort']),
            new TwigFunction('app_general_filter_sort_objects', [$this, 'sortObjects'])
        ];
    }

    public function translateValues(array $values): array
    {
        foreach ($values as &$modifyingValue) {
            $modifyingValue['translated'] = $this->translator->trans(mb_strtolower('attribute.' . $modifyingValue['value']));
        }

        return $values;
    }

    public function sort(array $values, string $fieldname = null): array
    {
        @usort($values, function ($left, $right) use ($fieldname) {
            $leftString = $left;
            $rightString = $right;

            if ($fieldname) {
                $methodName = 'get' . ucfirst($fieldname);
                if (is_array($leftString)) {
                    $leftString = $leftString[$fieldname];
                } elseif (method_exists($leftString, $methodName)) {
                    $leftString = $leftString->$methodName();
                }
                if (is_array($rightString)) {
                    $rightString = $rightString[$fieldname];
                } elseif (method_exists($rightString, $methodName)) {
                    $rightString = $rightString->$methodName();
                }
            }

            return strcmp($leftString, $rightString);
        });

        return $values;
    }

    public function sortObjects(array $values, string $fieldname, array $objects): array
    {
        @usort($values, function ($left, $right) use ($fieldname, $objects) {
            $leftString = '';
            $rightString = '';

            $methodName = 'get' . ucfirst($fieldname);

            $object = $objects[$left['value']];
            if ($object && method_exists($object, $methodName)) {
                $leftString = $object->$methodName();
            }

            $object = $objects[$right['value']];
            if ($object && method_exists($object, $methodName)) {
                $rightString = $object->$methodName();
            }

            return strcmp($leftString, $rightString);
        });

        return $values;
    }
}
